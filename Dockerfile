FROM image-registry.openshift-image-registry.svc:5000/openshift/php@sha256:5abe3f7ee738a9e1c9bc8a96353c197dda4fac021577b3edf492912c6151f7f5
USER root

# MySQL tools
RUN curl -L https://github.com/mprokopov/mysql-client-static/raw/master/bin/mysql -o /usr/bin/mysql &&     curl -L https://github.com/mprokopov/mysql-client-static/raw/master/bin/mysqldump -o /usr/bin/mysqldump &&     chmod +x /usr/bin/mysql /usr/bin/mysqldump

# Code Copy
COPY . /var/www/html/

# Apache එකට index.php එක පෙන්වන්න කියලා බල කිරීම
RUN echo "DirectoryIndex index.php index.html" >> /etc/httpd/conf.d/php.conf &&     rm -f /etc/httpd/conf.d/welcome.conf

# Permissions
RUN chown -R 1001:0 /var/www/html &&     chmod -R g+rwX /var/www/html

USER 1001
# S2I run script එක පාවිච්චි කිරීම
CMD ["/usr/libexec/s2i/run"]
