Write-Host "开始部署支付回调系统..." -ForegroundColor Green

Write-Host "`n1. 检查Git状态..." -ForegroundColor Yellow
git --no-pager status

Write-Host "`n2. 添加所有文件..." -ForegroundColor Yellow
git --no-pager add .

Write-Host "`n3. 提交更改..." -ForegroundColor Yellow
git --no-pager commit -m "Complete payment callback system"

Write-Host "`n4. 推送到GitHub..." -ForegroundColor Yellow
git --no-pager push origin master

Write-Host "`n部署完成！" -ForegroundColor Green
Write-Host "`n下一步：" -ForegroundColor Cyan
Write-Host "1. 访问 https://github.com/zhuiri-eng/zidonghua-" -ForegroundColor White
Write-Host "2. 确认代码已推送成功" -ForegroundColor White
Write-Host "3. 登录 https://netlify.com" -ForegroundColor White
Write-Host "4. 选择 'New site from Git'" -ForegroundColor White
Write-Host "5. 连接GitHub仓库并部署" -ForegroundColor White

Read-Host "`n按回车键继续..."
