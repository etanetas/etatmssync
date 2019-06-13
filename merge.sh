#!/bin/bash
git merge --no-commit --no-ff development
git reset HEAD -- src/*
git reset HEAD -- node_modules/*
git reset HEAD -- .gitignore
git clean --fd
git commit -m "Merged development"
