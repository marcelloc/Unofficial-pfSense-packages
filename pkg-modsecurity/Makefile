# $FreeBSD$

PORTNAME=	pfSense-pkg-ApacheModSecurity
PORTVERSION=	0.1
PORTREVISION=	2
CATEGORIES=	www
MASTER_SITES=	# empty
DISTFILES=	# empty
EXTRACT_ONLY=	# empty

MAINTAINER=	marcellocoutinho@gmail.com
COMMENT=	pfSense Unofficial apache modsecurity package

LICENSE=	APACHE20

RUN_DEPENDS=	${LOCALBASE}/libexec/apache24/mod_security3.so:security/modsecurity3-apache \
		libzip>0:archivers/libzip \
                php72-zip>0:archivers/php72-zip

NO_BUILD=	yes
NO_MTREE=	yes

SUB_FILES=	pkg-install pkg-deinstall
SUB_LIST=	PORTNAME=${PORTNAME}

do-extract:
	${MKDIR} ${WRKSRC}

do-install:
	${MKDIR} ${STAGEDIR}${PREFIX}/pkg
	${MKDIR} ${STAGEDIR}${PREFIX}/www
	${MKDIR} ${STAGEDIR}${PREFIX}/www/shortcuts
	${MKDIR} ${STAGEDIR}/etc/inc/priv
	${MKDIR} ${STAGEDIR}${DATADIR}
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/apache24.template \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/apache_mod_security.template \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/apache_settings.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/apache_balancer.template \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/apache_mod_security_groups.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/apache_virtualhost.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/apache_balancer.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/apache_mod_security_manipulation.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/apache_mod_security_settings.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/apache_location.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/apache_mod_security.inc \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/shortcuts/pkg_apache.inc \
		${STAGEDIR}${PREFIX}/www/shortcuts
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/apache_mod_security_sync.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${DATADIR}/info.xml \
		${STAGEDIR}${DATADIR}
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/apache_logs_data.php \
		${STAGEDIR}${PREFIX}/www
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/apache_mod_security_view_logs.php \
		${STAGEDIR}${PREFIX}/www
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/apache_logs_data.teste.php \
		${STAGEDIR}${PREFIX}/www
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/apache_view_logs.php \
		${STAGEDIR}${PREFIX}/www
	@${REINPLACE_CMD} -i '' -e "s|%%PKGVERSION%%|${PKGVERSION}|" \
		${STAGEDIR}${DATADIR}/info.xml

.include <bsd.port.mk>
