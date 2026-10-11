<%@ page contentType="text/html;charset=UTF-8" language="java" pageEncoding="UTF-8" %>
<%@ taglib prefix="c" uri="http://java.sun.com/jsp/jstl/core" %>
<%-- Already logged in? Go straight to the student list. --%>
<c:if test="${not empty sessionScope.loggedInUser}">
    <c:redirect url="/students"/>
</c:if>
<c:set var="ctx" value="${pageContext.request.contextPath}"/>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - College Student Portal</title>
    <link rel="stylesheet" href="${ctx}/css/style.css">
</head>
<body class="login-body">
<div class="login-card">
    <svg class="logo" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <circle cx="32" cy="32" r="30" fill="#e3edff" stroke="#1a5fd0" stroke-width="3"/>
        <polygon points="32,14 8,26 32,38 56,26" fill="#134aa0"/>
        <path d="M18 32v10c0 4 28 4 28 0V32l-14 7z" fill="#1a5fd0"/>
        <rect x="52" y="26" width="3" height="16" fill="#134aa0"/>
    </svg>
    <h1>College Student Portal</h1>
    <p class="tag">Please log in to continue</p>

    <c:if test="${not empty error}">
        <div class="alert alert-error"><c:out value="${error}"/></div>
    </c:if>
    <c:if test="${param.logout == '1'}">
        <div class="alert alert-success">You have been logged out successfully.</div>
    </c:if>

    <form action="${ctx}/login" method="post" onsubmit="return checkLoginForm();">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Enter username" autocomplete="username" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
        </div>
        <label class="show-pass">
            <input type="checkbox" id="showPass" onclick="togglePassword()"> Show password
        </label>
        <button type="submit" class="btn">Login</button>
    </form>
</div>

<script>
    function togglePassword() {
        var box = document.getElementById('password');
        box.type = document.getElementById('showPass').checked ? 'text' : 'password';
    }
    function checkLoginForm() {
        var u = document.getElementById('username').value.trim();
        var p = document.getElementById('password').value;
        if (u === '' || p === '') {
            alert('Please enter both username and password.');
            return false;
        }
        return true;
    }
</script>
</body>
</html>
