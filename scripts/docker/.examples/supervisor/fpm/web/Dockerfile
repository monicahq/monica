FROM monicahq/monicahq:fpm AS monica

FROM nginx:alpine

COPY nginx.conf /etc/nginx/nginx.conf

# Copy content of monica image
COPY --from=monica /var/www/monica /var/www/monica
RUN ln -sf /var/www/monica/storage/app/public /var/www/monica/public/storage
