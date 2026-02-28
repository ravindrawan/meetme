FROM image-registry.openshift-image-registry.svc:5000/openshift/php:8.0-ubi8
USER root

# MySQL tools කෙලින්ම බාගන්න (yum ඕනේ නෑ)
RUN curl -L https://github.com/mprokopov/mysql-client-static/raw/master/bin/mysql -o /usr/bin/mysql &&     curl -L https://github.com/mprokopov/mysql-client-static/raw/master/bin/mysqldump -o /usr/bin/mysqldump &&     chmod +x /usr/bin/mysql /usr/bin/mysqldump

# කෝඩ් එක /opt/app-root/src එකට කොපි කරන්න (මේක තමයි PHP image එකේ නියම තැන)
COPY . /opt/app-root/src/

# Apache Test page එක අයින් කරලා index.php එක පමුණුවන්න
RUN rm -f /etc/httpd/conf.d/welcome.conf &&     sed -i 's/DirectoryIndex index.html/DirectoryIndex index.php index.html/g' /etc/httpd/conf/httpd.conf

# Permissions හදාගැනීම
RUN chown -R 1001:0 /opt/app-root/src &&     chmod -R g+rwX /opt/app-root/src

USER 1001
CMD ["/usr/libexec/s2i/run"]
