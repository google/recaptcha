<%@ page contentType="text/html;charset=UTF-8" language="java" %>
<%@ page import="com.google.recaptcha.STokenUtils" %>

<%
  String siteKey = "6LdCSgETAAAAAOlx6NMvpAKGiVUMpRUG2lJEvEmx";
  String siteSecret = "6LdCSgETAAAAAEJGCBTBHoUEJ1B9WIvhKY_ET4to";
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
