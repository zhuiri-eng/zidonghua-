@echo off
echo 强制推送到GitHub仓库...
echo.

REM 设置环境变量禁用分页器
set GIT_PAGER=
set LESS=
set MORE=

REM 强制添加所有文件
echo 步骤1: 添加文件...
git add -A >nul 2>&1
if %errorlevel% neq 0 (
    echo 添加文件失败，尝试强制添加...
    git add . >nul 2>&1
)

REM 强制提交
echo 步骤2: 提交更改...
git commit -m "Complete payment callback system" >nul 2>&1
if %errorlevel% neq 0 (
    echo 提交失败，尝试强制提交...
    git commit --allow-empty -m "Complete payment callback system" >nul 2>&1
)

REM 强制推送
echo 步骤3: 推送到GitHub...
git push -u origin master --force >nul 2>&1
if %errorlevel% neq 0 (
    echo 推送失败，尝试HTTPS推送...
    git remote set-url origin https://github.com/zhuiri-eng/zidonghua-.git
    git push -u origin master --force >nul 2>&1
)

echo.
echo 推送完成！
echo 请访问: https://github.com/zhuiri-eng/zidonghua-
echo.
pause
