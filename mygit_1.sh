#!/usr/bin/env bash

git remote set-url origin https://github.com/applumbrado/alumun.git

## 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

## pwd : postg  =  R=D7,Z)$F%q,Kj?CP,DM{1CFNTtQ1B@4=V!d

git config --global user.name "applumbrado"
git config --global color.ui true
git config core.fileMode false
git config --global push.default simple

git checkout main

git status

git rm -r --cached .csv
git rm -r --cached public/csv
git rm -r --cached public/csv/

git rm -r --cached .env
git rm -r --cached .env.example
git rm -r --cached .env_prod
git rm -r --cached .gitignore
git rm -r --cached .gitattributes
git rm -r --cached ./.editorconfig
git rm -r --cached ./.buildconfig
git rm -r --cached .sh
git rm -r --cached mygit_0.sh
git rm -r --cached mygit_1.sh
git rm -r --cached mygit_2.sh
git rm -r --cached run_config.sh
git rm -r --cached .idea
git rm -r --cached .DS_Store
git rm -r --cached otros
git rm -r --cached laravel-echo-server.json
git rm -r --cached laravel-echo-server.lock
# git rm -r --cached vite.config.js

#git rm -r --cached node_modules

git rm -r --cached composer.json
git rm -r --cached composer.lock

git rm -r --cached storage/logs/
git rm -r --cached storage/logs

git add .

git commit -m "alumun - A0_a | LVIV 12.38 Production"

git push -u origin main --force

# Para ver si no hay archivos grandes
# git lfs install
# git lfs ls-files

exit
