#
# postgresql.dockerfile - a dockerfile to create a postgresql server image.
#
# created: 2023-06-11
# author: albert r. carnier guedes (albert@teko.net.br)
# 
# Distributed under the MIT License. See LICENSE for more information.
#
FROM postgres:alpine

# Create a default database and user to postgresql.
ENV POSTGRES_DB gacdb
ENV POSTGRES_USER gac
ENV POSTGRES_PASSWORD gac

# Copy the script to the default initialization directory of PostgreSQL
COPY init.sql /docker-entrypoint-initdb.d/