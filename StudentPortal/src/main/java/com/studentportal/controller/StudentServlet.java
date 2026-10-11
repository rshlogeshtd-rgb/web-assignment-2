package com.studentportal.controller;

import com.studentportal.dao.FileDAO;
import com.studentportal.dao.StudentDAO;
import com.studentportal.model.Student;

import javax.servlet.ServletException;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

/**
 * URL: /students            -> list of all students (optional ?q=search)
 * URL: /student?id=1        -> profile of one student
 */
@WebServlet(urlPatterns = {"/students", "/student"})
public class StudentServlet extends HttpServlet {

    private static final long serialVersionUID = 1L;
    private final StudentDAO studentDAO = new StudentDAO();
    private final FileDAO fileDAO = new FileDAO();

    @Override
    protected void doGet(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {
        if ("/student".equals(request.getServletPath())) {
            showProfile(request, response);
        } else {
            listStudents(request, response);
        }
    }

    private void listStudents(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {

        String keyword = request.getParameter("q");
        keyword = keyword == null ? "" : keyword.trim();

        List<Student> students = new ArrayList<>();
        try {
            students = studentDAO.getAllStudents(keyword);
            for (Student s : students) {
                s.setFiles(fileDAO.getFilesByStudentId(s.getId()));
            }
        } catch (SQLException e) {
            log("Database error while loading students", e);
            students = new ArrayList<>();
            request.setAttribute("errorMessage", "Unable to load students right now. Please check the database connection.");
        }

        request.setAttribute("students", students);
        request.setAttribute("keyword", keyword);
        request.getRequestDispatcher("/students.jsp").forward(request, response);
    }

    private void showProfile(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {

        int id;
        try {
            id = Integer.parseInt(request.getParameter("id"));
            if (id <= 0) {
                throw new NumberFormatException("id must be positive");
            }
        } catch (NumberFormatException e) {
            response.sendRedirect(request.getContextPath() + "/students?error=invalid");
            return;
        }

        try {
            Student student = studentDAO.getStudentById(id);
            if (student == null) {
                response.sendRedirect(request.getContextPath() + "/students?error=notfound");
                return;
            }
            student.setFiles(fileDAO.getFilesByStudentId(id));
            request.setAttribute("student", student);
            request.getRequestDispatcher("/student-profile.jsp").forward(request, response);

        } catch (SQLException e) {
            log("Database error while loading student " + id, e);
            response.sendRedirect(request.getContextPath() + "/students?error=db");
        }
    }
}
