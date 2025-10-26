FROM php:8.2-cli

WORKDIR /app

# Copia o código da aplicação
COPY . /app

# Expõe uma porta (Render fornece a porta via $PORT)
EXPOSE 8080

# Usa o servidor embutido do PHP e respeita a variável de ambiente $PORT fornecida pelo Render
CMD ["bash", "-lc", "php -S 0.0.0.0:$PORT proxy-n8n.php"]
