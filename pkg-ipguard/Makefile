# $FreeBSD$

PORTNAME=	pfSense-pkg-IPguard
PORTVERSION=	0.1
CATEGORIES=	www
MASTER_SITES=	# empty
DISTFILES=	# empty
EXTRACT_ONLY=	# empty

MAINTAINER=	marcellocoutinho@gmail.com
COMMENT=	pfSense Unofficial ipguard package

LICENSE=	APACHE20

RUN_DEPENDS=	${LOCALBASE}/sbin/ipguard:${PORTSDIR}/security/ipguard

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
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/ipguard.xml \
		${STAGEDIR}${PREFIX}/pkg
	 ${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/shortcuts/pkg_ipguard.inc \
		${STAGEDIR}${PREFIX}/www/shortcuts
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/ipguard_sync.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}/etc/inc/priv/ipguard.priv.inc \
		${STAGEDIR}/etc/inc/priv
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/ipguard.inc \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${DATADIR}/info.xml \
		${STAGEDIR}${DATADIR}
	@${REINPLACE_CMD} -i '' -e "s|%%PKGVERSION%%|${PKGVERSION}|" \
		${STAGEDIR}${DATADIR}/info.xml
	@${REINPLACE_CMD} -i '' -e "s|%%PKGVERSION%%|${PKGVERSION}|" \
		${FILESDIR}${PREFIX}/pkg/ipguard.xml

.include <bsd.port.mk>
