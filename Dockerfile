FROM image-registry.openshift-image-registry.svc:5000/openshift/php:8.0-ubi8
USER root
RUN yum install -y mysql && yum clean all
COPY upload/src /var/www/html/
RUN chown -R 1001:0 /var/www/html && chmod -R g+rwX /var/www/html
USER 1001
CMD ["/usr/libexec/s2i/run"]
