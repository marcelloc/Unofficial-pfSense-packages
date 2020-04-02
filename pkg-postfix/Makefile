# $FreeBSD$

PORTNAME=	pfSense-pkg-postfix
PORTVERSION=	3.4.9
PORTREVISION=	2	
CATEGORIES=	mail
MASTER_SITES=	# empty
DISTFILES=	# empty
EXTRACT_ONLY=	# empty

MAINTAINER=	marcellocoutinho@gmail.com
COMMENT=	pfSense package Postfix Forwarder.

RUN_DEPENDS=	postfix:mail/postfix-sasl \
		p5-perl-ldap>0:net/p5-perl-ldap \
		opendkim:mail/opendkim \
		pflogsumm:mail/pflogsumm \
		libspf2>0:mail/libspf \
		bash:shells/bash \
		libzip>0:archivers/libzip \
		php72-zip>0:archivers/php72-zip \
		policyd-spf:mail/postfix-policyd-spf-python \
		postfwd:mail/postfix-postfwd \
		zip:archivers/zip

USE_PERL5=	run
USES=		perl5

NO_BUILD=	yes
NO_MTREE=	yes

SUB_FILES=	pkg-install pkg-deinstall
SUB_LIST=	PORTNAME=${PORTNAME}

do-extract:
	${MKDIR} ${WRKSRC}

do-install:
	${MKDIR} ${STAGEDIR}${PREFIX}/pkg
	${MKDIR} ${STAGEDIR}${PREFIX}/www/shortcuts
	${MKDIR} ${STAGEDIR}/etc/inc/priv
	${MKDIR} ${STAGEDIR}${PREFIX}/www/widgets/widgets
	${MKDIR} ${STAGEDIR}${PREFIX}/www
	${MKDIR} ${STAGEDIR}${PREFIX}/www/vendor
	${MKDIR} ${STAGEDIR}${PREFIX}/www/vendor/datatable
	${MKDIR} ${STAGEDIR}${PREFIX}/www/vendor/datatable/js
	${MKDIR} ${STAGEDIR}${PREFIX}/www/vendor/datatable/css
	${MKDIR} ${STAGEDIR}${PREFIX}/bin
	${MKDIR} ${STAGEDIR}${DATADIR}
	${INSTALL_DATA} ${FILESDIR}/etc/inc/priv/postfix.priv.inc \
		${STAGEDIR}/etc/inc/priv
	${INSTALL_DATA} ${FILESDIR}${DATADIR}/info.xml \
		${STAGEDIR}${DATADIR}
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/bin/check_postfix_service.php \
		${STAGEDIR}${PREFIX}/bin
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/bin/mail_report.sh \
		${STAGEDIR}${PREFIX}/bin
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/bin/postfix_report.php \
		${STAGEDIR}${PREFIX}/bin
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/bin/adexport.pl \
		${STAGEDIR}${PREFIX}/bin
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/bin/postwhite \
		${STAGEDIR}${PREFIX}/bin
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/bin/sa-learn-pipe.sh \
		${STAGEDIR}${PREFIX}/bin
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/bin/sigh \
		${STAGEDIR}${PREFIX}/bin
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/postfix.inc \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/postfix.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/postfix_acl.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/postfix_antispam.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/postfix_dkim.inc \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/postfix_dmarc.inc \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/postfix_domains.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/postfix_postfwd.inc \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/postfix_postwhite.template \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/postfix_recipients.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/postfix_sync.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/postfix.php \
		${STAGEDIR}${PREFIX}/www
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/postfix.sql.php \
		${STAGEDIR}${PREFIX}/www
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/postfix_about.php \
		${STAGEDIR}${PREFIX}/www
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/postfix_cloud_domains.php \
		${STAGEDIR}${PREFIX}/www
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/postfix_migrate_config.php \
		${STAGEDIR}${PREFIX}/www
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/postfix_queue.php \
		${STAGEDIR}${PREFIX}/www
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/postfix_recipients.php \
		${STAGEDIR}${PREFIX}/www
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/postfix_search.php \
		${STAGEDIR}${PREFIX}/www
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/postfix_view_config.php \
		${STAGEDIR}${PREFIX}/www
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/shortcuts/pkg_postfix.inc \
		${STAGEDIR}${PREFIX}/www/shortcuts
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/vendor/datatable/css/jquery.dataTables.postfix.css \
		${STAGEDIR}${PREFIX}/www/vendor/datatable/css
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/vendor/datatable/datatables.css \
		${STAGEDIR}${PREFIX}/www/vendor/datatable
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/vendor/datatable/datatables.js \
		${STAGEDIR}${PREFIX}/www/vendor/datatable
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/vendor/datatable/datatables.min.css \
		${STAGEDIR}${PREFIX}/www/vendor/datatable
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/vendor/datatable/datatables.min.js \
		${STAGEDIR}${PREFIX}/www/vendor/datatable
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/vendor/datatable/js/jquery.dataTables.postfix.js \
		${STAGEDIR}${PREFIX}/www/vendor/datatable/js
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/widgets/widgets/postfix.widget.php \
		${STAGEDIR}${PREFIX}/www/widgets/widgets
.include <bsd.port.mk>

