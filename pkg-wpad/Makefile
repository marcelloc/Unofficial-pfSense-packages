# $FreeBSD$

PORTNAME=	pfSense-pkg-WPAD
PORTVERSION=	0.3.0.2
CATEGORIES=	www
MASTER_SITES=	# empty
DISTFILES=	# empty
EXTRACT_ONLY=	# empty

MAINTAINER=	marcellocoutinho@gmail.com
COMMENT=	pfSense Unofficial wpad package

LICENSE=	APACHE20

NO_BUILD=	yes
NO_MTREE=	yes

SUB_FILES=	pkg-install pkg-deinstall
SUB_LIST=	PORTNAME=${PORTNAME}

do-extract:
	${MKDIR} ${WRKSRC}

do-install:
	${MKDIR} ${STAGEDIR}${PREFIX}/pkg
	${MKDIR} ${STAGEDIR}/etc/inc/priv
	${MKDIR} ${STAGEDIR}${DATADIR}
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/wpad.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/wpad_sync.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}/etc/inc/priv/wpad.priv.inc \
		${STAGEDIR}/etc/inc/priv
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/wpad.inc \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/wpad_nginx.template \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/wpad_script.template \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/wpad_index.template \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${DATADIR}/info.xml \
		${STAGEDIR}${DATADIR}
	@${REINPLACE_CMD} -i '' -e "s|%%PKGVERSION%%|${PKGVERSION}|" \
		${STAGEDIR}${DATADIR}/info.xml
	@${REINPLACE_CMD} -i '' -e "s|%%PKGVERSION%%|${PKGVERSION}|" \
		${FILESDIR}${PREFIX}/pkg/wpad.xml

.include <bsd.port.mk>
