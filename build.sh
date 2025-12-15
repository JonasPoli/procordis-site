# online
rm -rf var/cache
/RunCloud/Packages/php83rc/bin/php bin/console asset-map:compile
/RunCloud/Packages/php83rc/bin/php bin/console tailwind:build

# local
symfony console asset-map:compile
symfony console tailwind:build


