// 主题同步脚本 - 供所有页面使用
(function() {
  'use strict';
  
  // 统一的主题管理函数
  function initializeTheme() {
    // 统一的主题优先级：localStorage > session > 默认值
    let savedTheme = localStorage.getItem('theme');
    
    // 如果没有localStorage中的主题，使用默认主题
    if (!savedTheme) {
      savedTheme = 'light';
      localStorage.setItem('theme', savedTheme);
    }
    
    // 设置主题
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    // 更新按钮图标（如果存在）
    const themeBtn = document.querySelector('.theme-toggle-btn');
    if (themeBtn) {
      themeBtn.innerHTML = savedTheme === 'light' ? '🌙' : '☀️';
      themeBtn.title = savedTheme === 'light' ? '切换到深色模式' : '切换到浅色模式';
    }
    
    console.log('主题初始化完成:', savedTheme);
    return savedTheme;
  }
  
  // 同步主题到其他页面
  function syncThemeToOtherPages(theme) {
    // 使用localStorage作为跨页面的主题存储
    localStorage.setItem('theme', theme);
    
    // 发送主题变化事件，供其他页面监听
    window.dispatchEvent(new CustomEvent('themeChanged', {
      detail: { theme: theme }
    }));
    
    // 如果支持BroadcastChannel，使用它进行跨标签页通信
    if (typeof BroadcastChannel !== 'undefined') {
      try {
        const channel = new BroadcastChannel('theme-sync');
        channel.postMessage({ theme: theme });
      } catch (e) {
        console.log('BroadcastChannel不可用:', e);
      }
    }
    
    console.log('主题已同步到其他页面:', theme);
  }
  
  // 主题切换功能
  function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    // 设置主题
    document.documentElement.setAttribute('data-theme', newTheme);
    
    // 更新按钮图标
    const themeBtn = document.querySelector('.theme-toggle-btn');
    if (themeBtn) {
      themeBtn.innerHTML = newTheme === 'light' ? '🌙' : '☀️';
      themeBtn.title = newTheme === 'light' ? '切换到深色模式' : '切换到浅色模式';
    }
    
    // 同步主题到其他页面
    syncThemeToOtherPages(newTheme);
    
    // 发送到服务器保存（如果存在change_theme.php）
    if (typeof fetch !== 'undefined') {
      fetch('change_theme.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'theme=' + newTheme
      }).then(response => response.json())
      .then(data => {
        if (data.success) {
          console.log('主题切换成功:', newTheme);
        } else {
          console.error('主题切换失败:', data.error);
        }
      }).catch(error => {
        console.error('主题切换请求失败:', error);
      });
    }
  }
  
  // 页面加载完成后初始化主题
  document.addEventListener('DOMContentLoaded', function() {
    // 初始化主题
    const currentTheme = initializeTheme();
    
    // 监听来自其他页面的主题变化
    window.addEventListener('themeChanged', function(event) {
      const newTheme = event.detail.theme;
      console.log('收到主题变化事件:', newTheme);
      
      // 更新当前页面的主题
      document.documentElement.setAttribute('data-theme', newTheme);
      
      // 更新按钮图标
      const themeBtn = document.querySelector('.theme-toggle-btn');
      if (themeBtn) {
        themeBtn.innerHTML = newTheme === 'light' ? '🌙' : '☀️';
        themeBtn.title = newTheme === 'light' ? '切换到深色模式' : '切换到浅色模式';
      }
    });
    
    // 监听BroadcastChannel消息
    if (typeof BroadcastChannel !== 'undefined') {
      try {
        const channel = new BroadcastChannel('theme-sync');
        channel.addEventListener('message', function(event) {
          const newTheme = event.data.theme;
          console.log('收到BroadcastChannel主题变化:', newTheme);
          
          // 更新当前页面的主题
          document.documentElement.setAttribute('data-theme', newTheme);
          
          // 更新按钮图标
          const themeBtn = document.querySelector('.theme-toggle-btn');
          if (themeBtn) {
            themeBtn.innerHTML = newTheme === 'light' ? '🌙' : '☀️';
            themeBtn.title = newTheme === 'light' ? '切换到深色模式' : '切换到浅色模式';
          }
        });
      } catch (e) {
        console.log('BroadcastChannel监听失败:', e);
      }
    }
  });
  
  // 将函数暴露到全局作用域，供其他脚本使用
  window.ThemeManager = {
    initializeTheme: initializeTheme,
    syncThemeToOtherPages: syncThemeToOtherPages,
    toggleTheme: toggleTheme
  };
  
  // 自动绑定主题切换按钮
  document.addEventListener('DOMContentLoaded', function() {
    const themeBtn = document.querySelector('.theme-toggle-btn');
    if (themeBtn && !themeBtn.onclick) {
      themeBtn.onclick = toggleTheme;
    }
  });
  
})();

