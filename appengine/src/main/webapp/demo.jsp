<%@ page contentType="text/html;charset=UTF-8" language="java" %>
<%@ page import="com.google.recaptcha.STokenUtils" %>

<%
  String siteKey = "site_key";
  String siteSecret = "site_secret";
%>

<html>
<head>
  <script src='//www.google.com/recaptcha/api.js'></script>
</head>
<body>
  <form>
    <div class="g-recaptcha" data-sitekey=<%=siteKey%>
      data-stoken=<%=STokenUtils.createSToken(siteSecret)%>></div>
  </form>
</body>
</html>
