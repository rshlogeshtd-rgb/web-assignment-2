package com.studentportal.dao;

import com.studentportal.model.StudentFile;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

/** Database access for the student_files table. */
public class FileDAO {

    private static final String COLUMNS =
            "id, student_id, original_file_name, file_path, file_type, upload_date";

    /** All files that belong to one student (newest first). */
    public List<StudentFile> getFilesByStudentId(int studentId) throws SQLException {
        String sql = "SELECT " + COLUMNS + " FROM student_files WHERE student_id = ? ORDER BY upload_date DESC, id DESC";
        List<StudentFile> files = new ArrayList<>();
        try (Connection con = DBConnection.getConnection();
             PreparedStatement ps = con.prepareStatement(sql)) {
            ps.setInt(1, studentId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    files.add(mapRow(rs));
                }
            }
        }
        return files;
    }

    /** One file by its id, or null if not found. */
    public StudentFile getFileById(int fileId) throws SQLException {
        String sql = "SELECT " + COLUMNS + " FROM student_files WHERE id = ?";
        try (Connection con = DBConnection.getConnection();
             PreparedStatement ps = con.prepareStatement(sql)) {
            ps.setInt(1, fileId);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return mapRow(rs);
                }
            }
        }
        return null;
    }

    private StudentFile mapRow(ResultSet rs) throws SQLException {
        StudentFile f = new StudentFile();
        f.setId(rs.getInt("id"));
        f.setStudentId(rs.getInt("student_id"));
        f.setOriginalFileName(rs.getString("original_file_name"));
        f.setFilePath(rs.getString("file_path"));
        f.setFileType(rs.getString("file_type"));
        f.setUploadDate(rs.getTimestamp("upload_date"));
        return f;
    }
}
