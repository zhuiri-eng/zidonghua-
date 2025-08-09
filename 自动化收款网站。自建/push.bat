@echo off
set GIT_PAGER=
set LESS=

echo 正在推送到GitHub仓库...
echo.

echo 添加文件...
git add . >nul 2>&1

echo 提交更改...
git commit -m "Complete payment callback system" >nul 2>&1

echo 推送到GitHub...
git push origin master

echo.
echo 推送完成！
echo 请访问: https://github.com/zhuiri-eng/zidonghua-
echo.
pause
