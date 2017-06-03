--- ./src/CertificateAuthority.orig.cpp	2017-03-13 13:05:36.000000000 -0300
+++ ./src/CertificateAuthority.cpp	2017-05-02 10:03:55.000000000 -0300
@@ -61,7 +61,7 @@
     _caCert = PEM_read_X509(fp, NULL, NULL, NULL);
     if (_caCert == NULL) {
         //syslog(LOG_ERR, "Couldn't load ca certificate");
-        log_ssl_errors("Couldn't load ca certificatei from %s", caCert);
+        log_ssl_errors("Couldn't load ca certificate from %s", caCert);
         //ERR_print_errors_fp(stderr);
         exit(1);
     }
@@ -118,7 +118,7 @@
     // added to generate different serial number than previous versions
     //   needs to be added as an option
     std::string sname(commonname );
-    sname += "A";
+    sname += "B";
 
 #ifdef DGDEBUG
     std::cout << "Generating serial no for " << commonname << std::endl;
@@ -133,8 +133,6 @@
         failed = true;
     }
 
-
-//    if (!failed && EVP_DigestUpdate(&mdctx, commonname, strlen(commonname)) < 1) {
     if (!failed && EVP_DigestUpdate(&mdctx, sname.c_str(), strlen(sname.c_str())) < 1) {
         failed = true;
     }
@@ -182,7 +180,7 @@
     // make directory path
     int rc = mkpath(dirpath.c_str(), 0700); // only want e2g to have access to these dir
     if (rc != 0) {
-        syslog(LOG_ERR, "error creating certificate sub-directory");
+        syslog(LOG_ERR, "error creating certificate sub-directory: %s", dirpath.c_str());
         exit(1);
     }
 
@@ -350,8 +348,6 @@
         return NULL;
     }
 
-    unsigned char *cn = (unsigned char *)commonname;
-
     //add the cn of the site we want a cert for the destination
     ERR_clear_error();
     int rc = X509_NAME_add_entry_by_txt(name, "CN",
@@ -389,6 +385,15 @@
         X509_free(newCert);
         return NULL;
     }
+    {
+    String temp1 = "DNS:";
+    String temp2 = commonname;
+    temp1 = temp1 + temp2;
+    char    *value = (char*) temp1.toCharArray();
+     if( !addExtension(newCert, NID_subject_alt_name, value))
+        log_ssl_errors("Error adding subjectAltName to the request", commonname);
+     }
+
 
     //sign it using the ca
     ERR_clear_error();
@@ -521,4 +526,18 @@
     if (_caPrivKey) EVP_PKEY_free(_caPrivKey);
     if (_certPrivKey) EVP_PKEY_free(_certPrivKey);
 }
+
+bool CertificateAuthority::addExtension(X509 *cert, int nid, char *value)
+{
+    X509_EXTENSION *ex = NULL;
+
+    ex = X509V3_EXT_conf_nid(NULL,NULL , nid, value);
+
+    int result = X509_add_ext(cert, ex, -1);
+
+    X509_EXTENSION_free(ex);
+
+    return (result > 0) ? true : false;
+}
+
 #endif //__SSLMITM
