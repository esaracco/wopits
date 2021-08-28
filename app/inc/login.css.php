/**
  CSS file for login page
*/

body,
html {
  margin: 0;
  padding: 0;
  height: 100%;
  overflow:auto;
}

#login-page .navbar {
  padding-left:20px;
  padding-right:20px;
}

#login-page .navbar-brand {
  background:none;
  color:#fff !important;
}

#login-page .themes * {
  float:left;
  margin-bottom:2px;
}

#login-page .main-login {
  height:80%;
  width:100%;
  margin-top:80px;
}

#login-page .links a,
#login-page .remember label {
  color:#a5a5a5;
  cursor:pointer;
  text-decoration:none;
}

#login-page .nowelcome label {
  color:#35b351;
}

#login-page .container {
  position: relative;
  top: 45%;
  transform: translateY(-50%);
}

#login-page #desc-container {
  position:absolute;
  top:80px;
  width:100%;
  z-index:2;
}

#login-page #desc-container .alert {
  margin-left:auto;
  margin-right:auto;
  max-width:600px;
  text-align: justify;
  -webkit-hyphens: auto;
  -moz-hyphens: auto;
  -ms-hyphens: auto;
  -o-hyphens: auto;
   hyphens: auto;
}

#login-page .user-card {
  z-index:5006; 
  height: 440px;
  width: 350px;
  margin-top: auto;
  margin-bottom: auto;
  background: #343a40;
  position: relative;
  display: flex;
  justify-content: center;
  flex-direction: column;
  padding: 10px;
  box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
  border: 2px solid #495057;
  border-radius: 5px;
}

#login-page .ldap .user-card {
  height: 370px;
}

#login-page .ldap .div-logo {
  margin-bottom:60px;
}

#login-page .brand-logo-container {
  position: absolute;
  height: 100px;
  width: 100px;
  top: 13px;
  border-radius: 50%;
  text-align: center;
}

#login-page .brand-logo {
  height: 100px;
  width: 100px;
  border-radius: 50%;
  border: 2px solid white;
  margin-top:8px;
}

#login-page .form-container {
  margin-top: 120px;
}

#login-page .ldap .form-container {
  margin-top: 40px;
}

#login-page #login button {
  width: 100%;
}

#login-page .user-card form span.required {
  background:#5e666f;
  color:#ff8383;
  border-radius:5px;
  padding:2px;
}
