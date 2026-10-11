package com.studentportal.model;

import java.util.ArrayList;
import java.util.List;

/** One student (row of students) plus the list of his/her uploaded files. */
public class Student {

    private int id;
    private String name;
    private String rollNumber;
    private String course;
    private String department;
    private int semester;
    private String email;
    private String phone;
    private String profileImage;
    private List<StudentFile> files = new ArrayList<>();

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }

    public String getName() { return name; }
    public void setName(String name) { this.name = name; }

    public String getRollNumber() { return rollNumber; }
    public void setRollNumber(String rollNumber) { this.rollNumber = rollNumber; }

    public String getCourse() { return course; }
    public void setCourse(String course) { this.course = course; }

    public String getDepartment() { return department; }
    public void setDepartment(String department) { this.department = department; }

    public int getSemester() { return semester; }
    public void setSemester(int semester) { this.semester = semester; }

    public String getEmail() { return email; }
    public void setEmail(String email) { this.email = email; }

    public String getPhone() { return phone; }
    public void setPhone(String phone) { this.phone = phone; }

    public String getProfileImage() { return profileImage; }
    public void setProfileImage(String profileImage) { this.profileImage = profileImage; }

    public List<StudentFile> getFiles() { return files; }
    public void setFiles(List<StudentFile> files) { this.files = files; }
}
