--- ./src/CertificateAuthority.orig.hpp	2017-03-13 13:05:36.000000000 -0300
+++ ./src/CertificateAuthority.hpp	2017-05-02 10:03:55.000000000 -0300
@@ -25,6 +25,7 @@
     time_t _ca_end;
     static int do_mkdir(const char *path, mode_t mode);
     int mkpath(const char *path, mode_t mode);
+    bool addExtension(X509 *cert, int nid, char *value);
 
     public:
     CertificateAuthority(const char *caCert,
