#!bin/bash
# Symfony 4.4 LTS ubuntu/debian

HTTP_USER=$(ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1)
setfacl -dR -m u:"$HTTP_USER":rwX -m u:"$(whoami)":rwX var
setfacl -R -m u:"$HTTP_USER":rwX -m u:"$(whoami)":rwX var
echo "Permissions set up!"