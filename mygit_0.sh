#!/usr/bin/env bash
echo "# SISTEMA DE ADMINISTRACION DE RECIBOS DE CFE" >> README.md
echo "## WebApp :: AluMun" >> README.md
echo "### " >> README.md
echo "### Invierno del 2025 " >> README.md
echo "#### " >> README.md
echo "#### Por @Ch50Dev" >> README.md
git init
git add README.md
git commit -m "Inicio"
git branch -M main
git remote add origin https://github.com/applumbrado/alumun.git
git push -u origin main

#echo "" > .gitignore
#git add .gitignore
git commit -m "message" .gitignore

git remote set-url origin https://github.com/applumbrado/alumun.git
git config --global user.email "alumbrado_app@centro.gob.mx"
git config --global user.name "applumbrado"
git config --global color.ui true
git config core.fileMode false
git config --global push.default simple

git checkout main

git status

git rm -r --cached .csv
git rm -r --cached public/csv
git rm -r --cached public/csv/


git add .

git commit -m "Init Commit"

git push -u origin main --force

exit


