<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AJAX测试页面</title>
</head>
<body>
    <h1>AJAX功能测试</h1>
    
    <button onclick="testAjax()">测试AJAX请求</button>
    <button onclick="testDeleteAjax()">测试删除AJAX请求</button>
    
    <div id="result"></div>
    
    <script>
        function testAjax() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '正在测试基本AJAX...';
            
            // 测试基本AJAX请求
            fetch('./public/ajax/delete_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'test',
                    test: 'hello'
                })
            })
            .then(response => {
                console.log('响应状态:', response.status);
                return response.text();
            })
            .then(data => {
                console.log('响应数据:', data);
                resultDiv.innerHTML = '<p style="color: green;">✓ AJAX请求成功</p><pre>' + data + '</pre>';
            })
            .catch(error => {
                console.error('AJAX错误:', error);
                resultDiv.innerHTML = '<p style="color: red;">✗ AJAX请求失败: ' + error.message + '</p>';
            });
        }
        
        function testDeleteAjax() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '正在测试删除AJAX...';
            
            // 测试删除AJAX请求
            fetch('./public/ajax/delete_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete_multiple',
                    report_ids: [999, 998] // 使用不存在的ID进行测试
                })
            })
            .then(response => {
                console.log('响应状态:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('响应数据:', data);
                resultDiv.innerHTML = '<p style="color: green;">✓ 删除AJAX请求成功</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            })
            .catch(error => {
                console.error('删除AJAX错误:', error);
                resultDiv.innerHTML = '<p style="color: red;">✗ 删除AJAX请求失败: ' + error.message + '</p>';
            });
        }
    </script>
</body>
</html>












