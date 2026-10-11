package com.studentportal.dao;

import com.studentportal.model.Student;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

/** Database access for the students table. */
public class StudentDAO {

    private static final String COLUMNS =
            "id, name, roll_number, course, department, semester, email, phone, profile_image";

    /** Returns all students, or only the matching ones when a search keyword is given. */
    public List<Student> getAllStudents(String keyword) throws SQLException {
        boolean hasKeyword = keyword != null && !keyword.trim().isEmpty();

        String sql = "SELECT " + COLUMNS + " FROM students";
        if (hasKeyword) {
            sql += " WHERE name LIKE ? OR roll_number LIKE ? OR course LIKE ? OR department LIKE ?";
        }
        sql += " ORDER BY name";

        List<Student> students = new ArrayList<>();
        try (Connection con = DBConnection.getConnection();
             PreparedStatement ps = con.prepareStatement(sql)) {

            if (hasKeyword) {
                String like = "%" + keyword.trim() + "%";
                ps.setString(1, like);
                ps.setString(2, like);
                ps.setString(3, like);
                ps.setString(4, like);
            }
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    students.add(mapRow(rs));
                }
            }
        }
        return students;
    }

    /** Returns one student by id, or null if not found. */
    public Student getStudentById(int id) throws SQLException {
        String sql = "SELECT " + COLUMNS + " FROM students WHERE id = ?";
        try (Connection con = DBConnection.getConnection();
             PreparedStatement ps = con.prepareStatement(sql)) {
            ps.setInt(1, id);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return mapRow(rs);
                }
            }
        }
        return null;
    }

    /** Returns the stored (relative) profile image path of a student, or null. */
    public String getProfileImagePath(int studentId) throws SQLException {
        String sql = "SELECT profile_image FROM students WHERE id = ?";
        try (Connection con = DBConnection.getConnection();
             PreparedStatement ps = con.prepareStatement(sql)) {
            ps.setInt(1, studentId);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return rs.getString("profile_image");
                }
            }
        }
        return null;
    }

    private Student mapRow(ResultSet rs) throws SQLException {
        Student s = new Student();
        s.setId(rs.getInt("id"));
        s.setName(rs.getString("name"));
        s.setRollNumber(rs.getString("roll_number"));
        s.setCourse(rs.getString("course"));
        s.setDepartment(rs.getString("department"));
        s.setSemester(rs.getInt("semester"));
        s.setEmail(rs.getString("email"));
        s.setPhone(rs.getString("phone"));
        s.setProfileImage(rs.getString("profile_image"));
        return s;
    }
}
