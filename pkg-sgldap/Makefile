# $FreeBSD$

PORTNAME=	pfSense-pkg-sgldap
PORTVERSION=	1.0
#PORTREVISION=	2
CATEGORIES=	sysutils
MASTER_SITES=	# empty
DISTFILES=	# empty
EXTRACT_ONLY=	# empty

MAINTAINER=	marcellocoutinho@gmail.com
COMMENT=	pfSense Unofficial filer package

LICENSE=	APACHE20

NO_BUILD=	yes
NO_MTREE=	yes

SUB_FILES=	pkg-install pkg-deinstall
SUB_LIST=	PORTNAME=${PORTNAME}

do-extract:
	${MKDIR} ${WRKSRC}

do-install:
	${MKDIR} ${STAGEDIR}${PREFIX}/pkg
	${MKDIR} ${STAGEDIR}${PREFIX}/bin
	${MKDIR} ${STAGEDIR}/etc/inc/priv
	${MKDIR} ${STAGEDIR}${DATADIR}
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/sgldap.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/sgldap.inc \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0755 ${FILESDIR}${PREFIX}/bin/squidguard_ldap.php \
		${STAGEDIR}${PREFIX}/bin
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/squidguard.sgldap.inc \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/squidguard.sgldap.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/squidguard_acl.sgldap.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/squidguard_configurator.sgldap.inc \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${DATADIR}/info.xml \
		${STAGEDIR}${DATADIR}
	@${REINPLACE_CMD} -i '' -e "s|%%PKGVERSION%%|${PKGVERSION}|" \
		${STAGEDIR}${DATADIR}/info.xml

.include <bsd.port.mk>
