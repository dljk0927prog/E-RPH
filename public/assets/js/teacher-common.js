/**
 * 老师页面通用JavaScript功能
 * 支持昼夜转换、动画效果、表单验证等
 */

// 主题管理
const ThemeManager = {
  // 初始化主题
  init() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    this.setTheme(savedTheme);
    this.updateThemeButton(savedTheme);
  },

  // 切换主题
  toggle() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    this.setTheme(newTheme);
    this.updateThemeButton(newTheme);
    localStorage.setItem('theme', newTheme);
  },

  // 设置主题
  setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
  },

  // 更新主题按钮
  updateThemeButton(theme) {
    const themeBtn = document.querySelector('.theme-toggle-btn');
    if (themeBtn) {
      themeBtn.innerHTML = theme === 'dark' ? '☀️' : '🌙';
      themeBtn.title = theme === 'dark' ? '切换到浅色模式' : '切换到深色模式';
    }
  }
};

// 动画管理
const AnimationManager = {
  // 页面加载动画
  addPageLoadAnimation() {
    const elements = document.querySelectorAll('.card, .stat-card, .main-content, .sidebar');
    elements.forEach((el, index) => {
      el.style.animationDelay = `${index * 0.1}s`;
      el.classList.add('fade-in-up');
    });
  },

  // 主题切换动画
  addThemeTransitionAnimation() {
    const body = document.body;
    body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
    
    const cards = document.querySelectorAll('.card, .stat-card, .main-content, .sidebar');
    cards.forEach(card => {
      card.style.transition = 'background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease';
    });
  },

  // 添加滚动动画
  addScrollAnimation() {
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('fade-in-up');
        }
      });
    }, observerOptions);

    document.querySelectorAll('.card, .stat-card').forEach(el => {
      observer.observe(el);
    });
  }
};

// 表单管理
const FormManager = {
  // 初始化表单
  init() {
    this.addFormValidation();
    this.addFormAnimations();
  },

  // 添加表单验证
  addFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
      form.addEventListener('submit', this.handleFormSubmit.bind(this));
    });
  },

  // 处理表单提交
  handleFormSubmit(event) {
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    
    if (submitBtn) {
      submitBtn.classList.add('loading');
      submitBtn.disabled = true;
    }
  },

  // 添加表单动画
  addFormAnimations() {
    const inputs = document.querySelectorAll('.form-input, .form-select, .form-textarea');
    inputs.forEach(input => {
      input.addEventListener('focus', this.handleInputFocus.bind(this));
      input.addEventListener('blur', this.handleInputBlur.bind(this));
    });
  },

  // 处理输入框聚焦
  handleInputFocus(event) {
    const input = event.target;
    input.parentElement.classList.add('focused');
  },

  // 处理输入框失焦
  handleInputBlur(event) {
    const input = event.target;
    if (!input.value) {
      input.parentElement.classList.remove('focused');
    }
  }
};

// 表格管理
const TableManager = {
  // 初始化表格
  init() {
    this.addTableSorting();
    this.addTableSearch();
    this.addTablePagination();
  },

  // 添加表格排序
  addTableSorting() {
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
      const headers = table.querySelectorAll('th[data-sort]');
      headers.forEach(header => {
        header.addEventListener('click', this.handleSort.bind(this, table, header));
      });
    });
  },

  // 处理排序
  handleSort(table, header) {
    const column = header.dataset.sort;
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const isAscending = header.classList.contains('sort-asc');
    
    rows.sort((a, b) => {
      const aValue = a.querySelector(`td[data-${column}]`).textContent;
      const bValue = b.querySelector(`td[data-${column}]`).textContent;
      
      if (isAscending) {
        return bValue.localeCompare(aValue);
      } else {
        return aValue.localeCompare(bValue);
      }
    });
    
    const tbody = table.querySelector('tbody');
    rows.forEach(row => tbody.appendChild(row));
    
    // 更新排序状态
    table.querySelectorAll('th').forEach(th => th.classList.remove('sort-asc', 'sort-desc'));
    header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
  },

  // 添加表格搜索
  addTableSearch() {
    const searchInputs = document.querySelectorAll('.table-search');
    searchInputs.forEach(input => {
      input.addEventListener('input', this.handleTableSearch.bind(this, input));
    });
  },

  // 处理表格搜索
  handleTableSearch(input) {
    const searchTerm = input.value.toLowerCase();
    const table = input.closest('.table-container').querySelector('.table');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
  },

  // 添加表格分页
  addTablePagination() {
    const paginationContainers = document.querySelectorAll('.pagination');
    paginationContainers.forEach(container => {
      this.initPagination(container);
    });
  },

  // 初始化分页
  initPagination(container) {
    const table = container.previousElementSibling;
    const rowsPerPage = parseInt(container.dataset.rowsPerPage) || 10;
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const totalPages = Math.ceil(rows.length / rowsPerPage);
    
    this.renderPagination(container, totalPages, rowsPerPage, rows);
  },

  // 渲染分页
  renderPagination(container, totalPages, rowsPerPage, rows) {
    container.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    // 上一页按钮
    const prevBtn = document.createElement('button');
    prevBtn.textContent = '上一页';
    prevBtn.className = 'btn btn-outline btn-sm';
    prevBtn.addEventListener('click', () => this.goToPage(container, 'prev', rowsPerPage, rows));
    container.appendChild(prevBtn);
    
    // 页码按钮
    for (let i = 1; i <= totalPages; i++) {
      const pageBtn = document.createElement('button');
      pageBtn.textContent = i;
      pageBtn.className = 'btn btn-outline btn-sm';
      pageBtn.addEventListener('click', () => this.goToPage(container, i, rowsPerPage, rows));
      container.appendChild(pageBtn);
    }
    
    // 下一页按钮
    const nextBtn = document.createElement('button');
    nextBtn.textContent = '下一页';
    nextBtn.className = 'btn btn-outline btn-sm';
    nextBtn.addEventListener('click', () => this.goToPage(container, 'next', rowsPerPage, rows));
    container.appendChild(nextBtn);
    
    // 显示第一页
    this.showPage(container, 1, rowsPerPage, rows);
  },

  // 跳转到指定页
  goToPage(container, page, rowsPerPage, rows) {
    const currentPage = parseInt(container.dataset.currentPage) || 1;
    let newPage;
    
    if (page === 'prev') {
      newPage = Math.max(1, currentPage - 1);
    } else if (page === 'next') {
      newPage = Math.min(Math.ceil(rows.length / rowsPerPage), currentPage + 1);
    } else {
      newPage = page;
    }
    
    this.showPage(container, newPage, rowsPerPage, rows);
  },

  // 显示指定页
  showPage(container, page, rowsPerPage, rows) {
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    
    rows.forEach((row, index) => {
      row.style.display = (index >= start && index < end) ? '' : 'none';
    });
    
    container.dataset.currentPage = page;
    
    // 更新按钮状态
    const buttons = container.querySelectorAll('button');
    buttons.forEach((btn, index) => {
      if (index === 0) { // 上一页
        btn.disabled = page === 1;
      } else if (index === buttons.length - 1) { // 下一页
        btn.disabled = page === Math.ceil(rows.length / rowsPerPage);
      } else { // 页码
        btn.classList.toggle('btn-primary', parseInt(btn.textContent) === page);
        btn.classList.toggle('btn-outline', parseInt(btn.textContent) !== page);
      }
    });
  }
};

// 消息管理
const MessageManager = {
  // 显示消息
  show(message, type = 'info', duration = 5000) {
    const messageEl = document.createElement('div');
    messageEl.className = `message ${type}`;
    messageEl.textContent = message;
    
    // 添加到页面
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(messageEl, container.firstChild);
    
    // 自动移除
    setTimeout(() => {
      messageEl.remove();
    }, duration);
    
    return messageEl;
  },

  // 显示成功消息
  success(message, duration) {
    return this.show(message, 'success', duration);
  },

  // 显示错误消息
  error(message, duration) {
    return this.show(message, 'error', duration);
  },

  // 显示警告消息
  warning(message, duration) {
    return this.show(message, 'warning', duration);
  }
};

// 工具函数
const Utils = {
  // 防抖函数
  debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  },

  // 节流函数
  throttle(func, limit) {
    let inThrottle;
    return function() {
      const args = arguments;
      const context = this;
      if (!inThrottle) {
        func.apply(context, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  },

  // 格式化日期
  formatDate(date, format = 'YYYY-MM-DD') {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    
    return format
      .replace('YYYY', year)
      .replace('MM', month)
      .replace('DD', day);
  },

  // 格式化数字
  formatNumber(num, decimals = 0) {
    return Number(num).toLocaleString('zh-CN', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    });
  }
};

// 初始化所有功能
document.addEventListener('DOMContentLoaded', function() {
  // 初始化主题
  ThemeManager.init();
  
  // 初始化动画
  AnimationManager.addPageLoadAnimation();
  AnimationManager.addThemeTransitionAnimation();
  AnimationManager.addScrollAnimation();
  
  // 初始化表单
  FormManager.init();
  
  // 初始化表格
  TableManager.init();
  
  // 绑定主题切换按钮
  const themeBtn = document.querySelector('.theme-toggle-btn');
  if (themeBtn) {
    themeBtn.addEventListener('click', () => ThemeManager.toggle());
  }
});

// 导出到全局作用域
window.TeacherCommon = {
  ThemeManager,
  AnimationManager,
  FormManager,
  TableManager,
  MessageManager,
  Utils
};
