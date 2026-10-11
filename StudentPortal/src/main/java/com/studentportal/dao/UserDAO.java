package com.studentportal.dao;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

/** Database access for the users table (login check). */
public class UserDAO {

    /**
     * Checks the username and password.
     * The password column stores SHA-256 hashes, so the typed password is hashed by MySQL with SHA2().
     *
     * @return the username if the login is valid, otherwise null
     */
    public String authenticate(String username, String password) throws SQLException {
        String sql = "SELECT username FROM users WHERE username = ? AND password = SHA2(?, 256)";
        try (Connection con = DBConnection.getConnection();
             PreparedStatement ps = con.prepareStatement(sql)) {
            ps.setString(1, username);
            ps.setString(2, password);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return rs.getString("username");
                }
            }
        }
        return null;
    }
}
