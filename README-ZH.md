# youyingxiang/weekly-report

[English](README.md)

一个 Laravel 扩展包，从 Git 提交记录和 GitHub Issues 自动生成周报，支持邮件预览确认后再正式发送。

## 工作流程

```
php artisan report:weekly
        |
        v
  扫描 git log，提取 #issue 编号
        |
        v
  调用 GitHub API 获取 issue 标题
        |
        v
  发送预览邮件给自己（带确认/取消按钮）
        |
        v
  点击「确认发送」 ──> 正式发送周报给指定收件人
  点击「取消」     ──> 丢弃本次周报
```

## 安装

```bash
composer require youyingxiang/weekly-report
```

## 配置

### 1. 发布配置文件

```bash
php artisan vendor:publish --tag=weekly-report-config
```

### 2. 环境变量

在 `.env` 中添加：

```env
# GitHub Token（私有仓库需要 repo scope）
WEEKLY_REPORT_GITHUB_TOKEN=ghp_xxxxxxxxxxxx

# 仓库配置
WEEKLY_REPORT_REPO_PATH=/path/to/your/repo
WEEKLY_REPORT_REPO_OWNER=your-org
WEEKLY_REPORT_REPO_NAME=your-repo
WEEKLY_REPORT_REPO_BRANCH=main

# 按作者过滤提交（可选）
WEEKLY_REPORT_GIT_AUTHOR=you@example.com

# 邮件配置
WEEKLY_REPORT_PREVIEW_TO=you@example.com
WEEKLY_REPORT_RECIPIENTS=boss@example.com,team@example.com
WEEKLY_REPORT_SUBJECT="Weekly Report ({week_start} - {week_end})"
```

### 3. 多仓库配置

编辑 `config/weekly-report.php`，添加多个仓库：

```php
'repositories' => [
    [
        'path'   => '/path/to/repo-a',
        'owner'  => 'your-org',
        'repo'   => 'repo-a',
        'branch' => 'main',
    ],
    [
        'path'   => '/path/to/repo-b',
        'owner'  => 'your-org',
        'repo'   => 'repo-b',
    ],
],
```

## 使用方法

### 生成并预览周报

```bash
php artisan report:weekly
```

执行后会：
1. 扫描本周的 git 提交记录
2. 从 commit message 中提取 `#issue` 编号
3. 通过 GitHub API 获取 issue 标题
4. 发送**预览邮件**给自己（包含确认/取消按钮，使用 signed URL）
5. 在邮件中点击**确认发送**，周报才会正式发送给所有收件人

### 命令参数

```bash
# 仅预览，不发送邮件
php artisan report:weekly --dry-run

# 生成上周的周报
php artisan report:weekly --weeks-ago=1

# 生成两周前的周报
php artisan report:weekly --weeks-ago=2
```

### 确认流程

- 在预览邮件中点击**确认发送**，周报将发送给所有收件人
- 点击**取消**，丢弃本次周报
- 链接默认 24 小时后过期（可通过 `WEEKLY_REPORT_URL_EXPIRATION` 配置，单位：分钟）

## 自定义模板

发布并自定义邮件/页面模板：

```bash
php artisan vendor:publish --tag=weekly-report-views
```

模板会复制到 `resources/views/vendor/weekly-report/` 目录。

## 目录结构

```
src/
├── Commands/WeeklyReportCommand.php    # report:weekly 命令
├── Http/Controllers/                   # Signed URL 确认/取消控制器
├── Mail/                               # 预览邮件和正式邮件
├── Services/
│   ├── GitLogParser.php                # Git log 解析 + issue 提取
│   ├── GitHubClient.php                # GitHub API 客户端
│   └── ReportGenerator.php             # 编排器
└── WeeklyReportServiceProvider.php     # 自动注册的 ServiceProvider
```

## License

MIT
