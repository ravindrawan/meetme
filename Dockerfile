FROM image-registry.openshift-image-registry.svc:5000/openshift/php:8.0-ubi8
USER root
# mysql මොඩියුලය enable කරලා ඉන්ස්ටෝල් කිරීම
RUN yum module enable -y mariadb:10.3 &&     yum install -y mariadb &&     yum clean all
COPY upload/src /var/www/html/
RUN chown -R 1001:0 /var/www/html && chmod -R g+rwX /var/www/html
USER 1001
CMD ["/usr/libexec/s2i/run"]
