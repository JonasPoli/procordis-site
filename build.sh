# O tailwind:build precisa rodar ANTES do asset-map:compile: o asset-map:compile copia para
# public/assets o CSS gerado pelo tailwind:build (var/tailwind/app.built.css). Na ordem inversa,
# a produção fica com o CSS do build anterior (classes novas dos templates não aparecem).

# online
rm -rf var/cache
/RunCloud/Packages/php83rc/bin/php bin/console tailwind:build
/RunCloud/Packages/php83rc/bin/php bin/console asset-map:compile

# local
symfony console tailwind:build
symfony console asset-map:compile


