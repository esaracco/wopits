body,
html {
  margin: 0;
  padding: 0;
  height: 100%;
  overflow:auto;
}

.main-login {
  height:80%;
  width:100%;
  margin-top:80px;
}

.login-page .links a,
.login-page .custom-checkbox label {
  color:#a5a5a5;
  cursor: pointer;
}

.container {
  position: relative;
  top: 45%;
  transform: translateY(-50%);
}

.user-card {
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

.brand-logo-container {
  position: absolute;
  height: 100px;
  width: 100px;
  top: 13px;
  border-radius: 50%;
  text-align: center;
}

.brand-logo {
  height: 100px;
  width: 100px;
  border-radius: 50%;
  border: 2px solid white;
  margin-top:8px;
}

.form-container {
  margin-top: 120px;
}

#login button {
  width: 100%;
}

.login-container {
  padding: 0 2rem;
}

.user-card form span.required {
  background:#5e666f;
  color:#ff8383;
  border-radius:5px;
  padding:2px;
}
