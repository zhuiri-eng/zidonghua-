@echo off
echo 开始部署支付回调系统...

echo.
echo 1. 检查Git状态...
git status

echo.
echo 2. 添加所有文件...
git add .

echo.
echo 3. 提交更改...
git commit -m "Update payment callback system"

echo.
echo 4. 推送到GitHub...
git push origin master

echo.
echo 部署完成！
echo.
echo 下一步：
echo 1. 访问 https://github.com/zhuiri-eng/zidonghua-
echo 2. 确认代码已推送成功
echo 3. 登录 https://netlify.com
echo 4. 选择 "New site from Git"
echo 5. 连接GitHub仓库并部署
echo.
pause
