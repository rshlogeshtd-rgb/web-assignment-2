<%@ page contentType="text/html;charset=UTF-8" language="java" pageEncoding="UTF-8" %>
<%@ taglib prefix="c" uri="http://java.sun.com/jsp/jstl/core" %>
<%@ taglib prefix="fn" uri="http://java.sun.com/jsp/jstl/functions" %>
<%@ taglib prefix="fmt" uri="http://java.sun.com/jsp/jstl/fmt" %>
<%-- Security: must be logged in, and must arrive through /student?id=... (so the data is loaded) --%>
<c:if test="${empty sessionScope.loggedInUser}">
    <c:redirect url="/login.jsp"/>
</c:if>
<c:if test="${student == null}">
    <c:redirect url="/students"/>
</c:if>
<c:set var="ctx" value="${pageContext.request.contextPath}"/>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><c:out value="${student.name}"/> - Student Profile</title>
    <link rel="stylesheet" href="${ctx}/css/style.css">
</head>
<body>

<nav class="navbar">
    <a class="brand" href="${ctx}/students">
        <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <polygon points="32,10 4,24 32,38 60,24" fill="#ffffff"/>
            <path d="M16 32v12c0 5 32 5 32 0V32l-16 8z" fill="#cfe0ff"/>
        </svg>
        College Student Portal
    </a>
    <div class="nav-right">
        <span class="user-chip">Logged in as <strong><c:out value="${sessionScope.loggedInUser}"/></strong></span>
        <a class="btn btn-light btn-sm" href="${ctx}/logout">Logout</a>
    </div>
</nav>

<div class="container">

    <div class="profile-actions">
        <a class="btn btn-outline" href="${ctx}/students">&larr; Back to Students</a>
        <a class="btn" href="${ctx}/logout">Logout</a>
    </div>

    <div class="profile-layout">

        <%-- Left: large profile image --%>
        <div class="card profile-side">
            <c:choose>
                <c:when test="${not empty student.profileImage}">
                    <img class="avatar avatar-lg" src="${ctx}/file?type=profile&amp;id=${student.id}"
                         alt="Profile photo of <c:out value='${student.name}'/>"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <div class="avatar avatar-lg avatar-placeholder" style="display:none;">
                        ${fn:toUpperCase(fn:substring(student.name, 0, 1))}
                    </div>
                </c:when>
                <c:otherwise>
                    <div class="avatar avatar-lg avatar-placeholder">
                        ${fn:toUpperCase(fn:substring(student.name, 0, 1))}
                    </div>
                    <span class="avatar-note">No profile image uploaded</span>
                </c:otherwise>
            </c:choose>
            <h2><c:out value="${student.name}"/></h2>
            <span class="badge"><c:out value="${student.rollNumber}"/></span>
        </div>

        <%-- Right: information + documents --%>
        <div>
            <div class="card" style="margin-bottom:24px;">
                <h3>Student Information</h3>
                <table class="info-table">
                    <tr><td>Student Name</td><td><c:out value="${student.name}"/></td></tr>
                    <tr><td>Roll Number</td><td><c:out value="${student.rollNumber}"/></td></tr>
                    <tr><td>Course</td><td><c:out value="${student.course}"/></td></tr>
                    <tr><td>Department</td><td><c:out value="${student.department}"/></td></tr>
                    <tr><td>Semester</td><td><c:out value="${student.semester}"/></td></tr>
                    <tr><td>Email</td>
                        <td>
                            <c:choose>
                                <c:when test="${not empty student.email}">
                                    <a href="mailto:<c:out value='${student.email}'/>"><c:out value="${student.email}"/></a>
                                </c:when>
                                <c:otherwise><span class="muted">Not provided</span></c:otherwise>
                            </c:choose>
                        </td>
                    </tr>
                    <tr><td>Phone Number</td>
                        <td>
                            <c:choose>
                                <c:when test="${not empty student.phone}"><c:out value="${student.phone}"/></c:when>
                                <c:otherwise><span class="muted">Not provided</span></c:otherwise>
                            </c:choose>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <h3>Uploaded Documents</h3>
                <c:choose>
                    <c:when test="${empty student.files}">
                        <p class="muted">No documents uploaded.</p>
                    </c:when>
                    <c:otherwise>
                        <ul class="doc-list">
                            <c:forEach var="f" items="${student.files}">
                                <li class="doc-item">
                                    <%-- icon / thumbnail --%>
                                    <c:choose>
                                        <c:when test="${f.image and not empty f.filePath}">
                                            <img class="doc-thumb" src="${ctx}/file?type=document&amp;id=${f.id}"
                                                 alt="Preview of <c:out value='${f.originalFileName}'/>">
                                        </c:when>
                                        <c:when test="${f.pdf}"><div class="doc-icon pdf">PDF</div></c:when>
                                        <c:otherwise><div class="doc-icon">FILE</div></c:otherwise>
                                    </c:choose>

                                    <div class="doc-info">
                                        <div class="doc-name"><c:out value="${f.originalFileName}"/></div>
                                        <div class="doc-meta">
                                            <c:out value="${f.fileType}"/> &middot;
                                            Uploaded <fmt:formatDate value="${f.uploadDate}" pattern="dd MMM yyyy, hh:mm a"/>
                                        </div>
                                    </div>

                                    <%-- buttons only when the document is available --%>
                                    <c:choose>
                                        <c:when test="${not empty f.filePath}">
                                            <div class="doc-actions">
                                                <a class="btn btn-sm" target="_blank" rel="noopener"
                                                   href="${ctx}/file?type=document&amp;id=${f.id}">View</a>
                                                <a class="btn btn-sm btn-outline"
                                                   href="${ctx}/file?type=document&amp;id=${f.id}&amp;download=1">Download</a>
                                            </div>
                                        </c:when>
                                        <c:otherwise>
                                            <span class="muted">File not available</span>
                                        </c:otherwise>
                                    </c:choose>
                                </li>
                            </c:forEach>
                        </ul>
                    </c:otherwise>
                </c:choose>
            </div>
        </div>
    </div>
</div>

<div class="footer">&copy; College Student Portal</div>
</body>
</html>
