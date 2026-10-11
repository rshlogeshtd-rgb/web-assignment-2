<%@ page contentType="text/html;charset=UTF-8" language="java" pageEncoding="UTF-8" %>
<%@ taglib prefix="c" uri="http://java.sun.com/jsp/jstl/core" %>
<%@ taglib prefix="fn" uri="http://java.sun.com/jsp/jstl/functions" %>
<%-- Security: must be logged in, and must arrive through /students (so the data is loaded) --%>
<c:if test="${empty sessionScope.loggedInUser}">
    <c:redirect url="/login.jsp"/>
</c:if>
<c:if test="${students == null}">
    <c:redirect url="/students"/>
</c:if>
<c:set var="ctx" value="${pageContext.request.contextPath}"/>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Students - College Student Portal</title>
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
    <h1 class="page-title">Students</h1>
    <p class="sub-title">Total students shown: <strong>${fn:length(students)}</strong></p>

    <c:if test="${not empty errorMessage}">
        <div class="alert alert-error"><c:out value="${errorMessage}"/></div>
    </c:if>
    <c:if test="${param.error == 'notfound'}">
        <div class="alert alert-error">That student was not found.</div>
    </c:if>
    <c:if test="${param.error == 'invalid'}">
        <div class="alert alert-error">Invalid student id.</div>
    </c:if>
    <c:if test="${param.error == 'db'}">
        <div class="alert alert-error">Could not load the student. Please check the database connection.</div>
    </c:if>

    <form class="search-bar" action="${ctx}/students" method="get">
        <input type="text" id="searchBox" name="q" value="<c:out value='${keyword}'/>"
               placeholder="Search by name, roll number, course or department..." autocomplete="off">
        <button type="submit" class="btn">Search</button>
        <c:if test="${not empty keyword}">
            <a class="btn btn-outline" href="${ctx}/students">Clear</a>
        </c:if>
    </form>

    <c:choose>
        <c:when test="${empty students}">
            <div class="empty-box">
                <h3>No students found.</h3>
                <p>Try a different search word.</p>
            </div>
        </c:when>
        <c:otherwise>
            <div class="grid" id="studentGrid">
                <c:forEach var="s" items="${students}">
                    <div class="student-card"
                         data-search="<c:out value='${s.name} ${s.rollNumber} ${s.course} ${s.department}'/>">

                        <%-- Profile image (served by FileServlet) or a placeholder --%>
                        <c:choose>
                            <c:when test="${not empty s.profileImage}">
                                <img class="avatar" src="${ctx}/file?type=profile&amp;id=${s.id}"
                                     alt="Profile photo of <c:out value='${s.name}'/>"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                <div class="avatar avatar-placeholder" style="display:none;">
                                    ${fn:toUpperCase(fn:substring(s.name, 0, 1))}
                                </div>
                            </c:when>
                            <c:otherwise>
                                <div class="avatar avatar-placeholder">
                                    ${fn:toUpperCase(fn:substring(s.name, 0, 1))}
                                </div>
                                <span class="avatar-note">No profile image</span>
                            </c:otherwise>
                        </c:choose>

                        <h3><c:out value="${s.name}"/></h3>
                        <p class="roll"><c:out value="${s.rollNumber}"/></p>
                        <p class="course"><c:out value="${s.course}"/></p>

                        <div class="doc-count">
                            <c:choose>
                                <c:when test="${empty s.files}">No documents uploaded.</c:when>
                                <c:otherwise>${fn:length(s.files)} document(s) uploaded</c:otherwise>
                            </c:choose>
                        </div>

                        <a class="btn" href="${ctx}/student?id=${s.id}">View Profile</a>
                    </div>
                </c:forEach>
            </div>
            <div class="empty-box" id="noMatch" style="display:none; margin-top:20px;">
                No students match your typing.
            </div>
        </c:otherwise>
    </c:choose>
</div>

<div class="footer">&copy; College Student Portal</div>

<script>
    // Live filter: hides cards while typing (the Search button does a full database search).
    (function () {
        var box = document.getElementById('searchBox');
        var cards = document.querySelectorAll('#studentGrid .student-card');
        var noMatch = document.getElementById('noMatch');
        if (!box || cards.length === 0) { return; }
        box.addEventListener('input', function () {
            var word = box.value.trim().toLowerCase();
            var shown = 0;
            cards.forEach(function (card) {
                var match = card.getAttribute('data-search').toLowerCase().indexOf(word) !== -1;
                card.style.display = match ? '' : 'none';
                if (match) { shown++; }
            });
            noMatch.style.display = shown === 0 ? 'block' : 'none';
        });
    })();
</script>
</body>
</html>
