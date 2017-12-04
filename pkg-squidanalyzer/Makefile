# $FreeBSD$

PORTNAME=	pfSense-pkg-Squidanalyzer
PORTVERSION=	0.2.1
CATEGORIES=	www
MASTER_SITES=	# empty
DISTFILES=	# empty
EXTRACT_ONLY=	# empty

MAINTAINER=	marcellocoutinho@gmail.com
COMMENT=	pfSense Unofficial wpad package

LICENSE=	APACHE20

RUN_DEPENDS=	${LOCALBASE}/bin/squid-analyzer:www/squidanalyzer

NO_BUILD=	yes
NO_MTREE=	yes

SUB_FILES=	pkg-install pkg-deinstall
SUB_LIST=	PORTNAME=${PORTNAME}

do-extract:
	${MKDIR} ${WRKSRC}

do-install:
	${MKDIR} ${STAGEDIR}${PREFIX}/pkg
	${MKDIR} ${STAGEDIR}${PREFIX}/www
	${MKDIR} ${STAGEDIR}/etc/inc/priv
	${MKDIR} ${STAGEDIR}${DATADIR}
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/squidanalyzer.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/squidanalyzer_sync.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}/etc/inc/priv/squidanalyzer.priv.inc \
		${STAGEDIR}/etc/inc/priv
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/squidanalyzer.inc \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/squidanalyzer.template \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squidanalyzer_frame.php \
		${STAGEDIR}${PREFIX}/www
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/www/squidanalyzer_reports.php \
		${STAGEDIR}${PREFIX}/www
	${INSTALL_DATA} -m 0644 ${FILESDIR}${DATADIR}/info.xml \
		${STAGEDIR}${DATADIR}
	@${REINPLACE_CMD} -i '' -e "s|%%PKGVERSION%%|${PKGVERSION}|" \
		${STAGEDIR}${DATADIR}/info.xml
	@${REINPLACE_CMD} -i '' -e "s|%%PKGVERSION%%|${PKGVERSION}|" \
		${FILESDIR}${PREFIX}/pkg/squidanalyzer.xml

.include <bsd.port.mk>
