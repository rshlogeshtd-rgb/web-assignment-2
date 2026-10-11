package com.studentportal.controller;

import com.studentportal.dao.FileDAO;
import com.studentportal.dao.StudentDAO;
import com.studentportal.model.StudentFile;

import javax.servlet.ServletException;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import javax.servlet.http.HttpSession;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.URLEncoder;
import java.nio.file.Files;
import java.nio.file.InvalidPathException;
import java.nio.file.NoSuchFileException;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.Map;

/**
 * Streams profile images and documents to the browser.
 *
 *   /file?type=profile&id=STUDENT_ID                 -> profile image
 *   /file?type=document&id=FILE_ID                   -> document shown in the browser
 *   /file?type=document&id=FILE_ID&download=1        -> document downloaded
 *
 * The browser only ever sees ids. The real server path stays hidden and is checked
 * against path traversal before the file is opened.
 */
@WebServlet("/file")
public class FileServlet extends HttpServlet {

    private static final long serialVersionUID = 1L;
    private static final int BUFFER_SIZE = 8192;

    private static final Map<String, String> CONTENT_TYPES = new HashMap<>();
    static {
        CONTENT_TYPES.put("jpg", "image/jpeg");
        CONTENT_TYPES.put("jpeg", "image/jpeg");
        CONTENT_TYPES.put("png", "image/png");
        CONTENT_TYPES.put("gif", "image/gif");
        CONTENT_TYPES.put("pdf", "application/pdf");
    }

    private final StudentDAO studentDAO = new StudentDAO();
    private final FileDAO fileDAO = new FileDAO();

    @Override
    protected void doGet(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {

        // 1. Only logged-in users
        HttpSession session = request.getSession(false);
        if (session == null || session.getAttribute("loggedInUser") == null) {
            response.sendError(HttpServletResponse.SC_UNAUTHORIZED, "Please log in.");
            return;
        }

        // 2. Validate the request
        String type = request.getParameter("type");
        int id = parseId(request.getParameter("id"));
        boolean isProfile = "profile".equals(type);
        boolean isDocument = "document".equals(type);
        if (id <= 0 || (!isProfile && !isDocument)) {
            response.sendError(HttpServletResponse.SC_BAD_REQUEST, "Invalid request.");
            return;
        }
        boolean download = "1".equals(request.getParameter("download"));

        // 3. Find the stored (relative) path in MySQL
        String relativePath;
        String displayName = null;
        try {
            if (isProfile) {
                relativePath = studentDAO.getProfileImagePath(id);
            } else {
                StudentFile f = fileDAO.getFileById(id);
                if (f == null) {
                    response.sendError(HttpServletResponse.SC_NOT_FOUND, "File not found.");
                    return;
                }
                relativePath = f.getFilePath();
                displayName = f.getOriginalFileName();
            }
        } catch (SQLException e) {
            log("Database error while reading file info", e);
            response.sendError(HttpServletResponse.SC_INTERNAL_SERVER_ERROR, "Unable to load the file.");
            return;
        }

        if (relativePath == null || relativePath.trim().isEmpty()) {
            response.sendError(HttpServletResponse.SC_NOT_FOUND, "File not found.");
            return;
        }

        // 4. Turn it into a safe real path inside the uploads folder
        Path file;
        try {
            file = resolveFile(getUploadBase(), relativePath.trim());
        } catch (SecurityException e) {
            log("Blocked suspicious file path: " + relativePath);
            response.sendError(HttpServletResponse.SC_FORBIDDEN, "Access denied.");
            return;
        } catch (FileNotFoundException | NoSuchFileException e) {
            response.sendError(HttpServletResponse.SC_NOT_FOUND, "File not found.");
            return;
        } catch (IOException e) {
            log("Upload folder problem", e);
            response.sendError(HttpServletResponse.SC_INTERNAL_SERVER_ERROR, "Unable to load the file.");
            return;
        }

        // 5. Choose the content type
        String fileName = file.getFileName().toString();
        String contentType = CONTENT_TYPES.getOrDefault(extensionOf(fileName), "application/octet-stream");
        if (isProfile && !contentType.startsWith("image/")) {
            response.sendError(HttpServletResponse.SC_UNSUPPORTED_MEDIA_TYPE, "Not an image.");
            return;
        }
        boolean canShowInBrowser = contentType.startsWith("image/") || contentType.equals("application/pdf");
        boolean asAttachment = download || !canShowInBrowser;

        if (displayName == null || displayName.trim().isEmpty()) {
            displayName = fileName;
        }

        // 6. Stream the file
        response.setContentType(contentType);
        response.setContentLengthLong(Files.size(file));
        response.setHeader("X-Content-Type-Options", "nosniff");
        response.setHeader("Content-Disposition", buildDisposition(asAttachment, displayName));

        try (InputStream in = Files.newInputStream(file);
             OutputStream out = response.getOutputStream()) {
            byte[] buffer = new byte[BUFFER_SIZE];
            int read;
            while ((read = in.read(buffer)) != -1) {
                out.write(buffer, 0, read);
            }
            out.flush();
        }
    }

    /** Real path of the uploads folder (from web.xml "uploadBasePath", or webapp/uploads). */
    private Path getUploadBase() throws IOException {
        String configured = getServletContext().getInitParameter("uploadBasePath");
        String folder;
        if (configured != null && !configured.trim().isEmpty()) {
            folder = configured.trim();
        } else {
            folder = getServletContext().getRealPath("/uploads");
        }
        if (folder == null) {
            throw new IOException("Upload folder is not available.");
        }
        return Paths.get(folder).toRealPath();
    }

    /** Resolves relativePath inside base and refuses anything that escapes the base folder. */
    private Path resolveFile(Path base, String relativePath) throws IOException {
        Path target;
        try {
            target = base.resolve(relativePath).normalize();
        } catch (InvalidPathException e) {
            throw new SecurityException("Invalid path");
        }
        if (!target.startsWith(base)) {
            throw new SecurityException("Path traversal attempt");
        }
        if (!Files.isRegularFile(target)) {
            throw new FileNotFoundException("Missing file");
        }
        Path real = target.toRealPath();
        if (!real.startsWith(base)) {
            throw new SecurityException("Path traversal attempt");
        }
        return real;
    }

    private int parseId(String value) {
        try {
            return Integer.parseInt(value);
        } catch (NumberFormatException e) {
            return -1;
        }
    }

    private String extensionOf(String name) {
        int dot = name.lastIndexOf('.');
        return dot < 0 ? "" : name.substring(dot + 1).toLowerCase();
    }

    private String buildDisposition(boolean attachment, String name) throws IOException {
        String asciiName = name.replaceAll("[^\\x20-\\x7E]", "_").replace("\"", "_").replace("\\", "_");
        String encoded = URLEncoder.encode(name, "UTF-8").replace("+", "%20");
        return (attachment ? "attachment" : "inline")
                + "; filename=\"" + asciiName + "\"; filename*=UTF-8''" + encoded;
    }
}
