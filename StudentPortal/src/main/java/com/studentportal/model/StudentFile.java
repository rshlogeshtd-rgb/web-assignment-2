package com.studentportal.model;

import java.sql.Timestamp;

/** One uploaded document that belongs to a student (row of student_files). */
public class StudentFile {

    private int id;
    private int studentId;
    private String originalFileName;
    private String filePath;
    private String fileType;
    private Timestamp uploadDate;

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }

    public int getStudentId() { return studentId; }
    public void setStudentId(int studentId) { this.studentId = studentId; }

    public String getOriginalFileName() { return originalFileName; }
    public void setOriginalFileName(String originalFileName) { this.originalFileName = originalFileName; }

    public String getFilePath() { return filePath; }
    public void setFilePath(String filePath) { this.filePath = filePath; }

    public String getFileType() { return fileType; }
    public void setFileType(String fileType) { this.fileType = fileType; }

    public Timestamp getUploadDate() { return uploadDate; }
    public void setUploadDate(Timestamp uploadDate) { this.uploadDate = uploadDate; }

    /** EL: ${file.image} */
    public boolean isImage() {
        return fileType != null && fileType.toLowerCase().startsWith("image/");
    }

    /** EL: ${file.pdf} */
    public boolean isPdf() {
        return fileType != null && fileType.equalsIgnoreCase("application/pdf");
    }
}
