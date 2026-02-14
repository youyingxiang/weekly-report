# yxx/weekly-report

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
  调用 GitHub API 获取 issue 标题（token 自动从 gh CLI 获取）
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
composer require yxx/weekly-report
```

## 配置

在 `.env` 中添加：

```env
WEEKLY_REPORT_PREVIEW_TO=you@example.com
WEEKLY_REPORT_RECIPIENTS=boss@example.com,team@example.com
```

只需要配这两项，其他全部自动检测：

| 配置项 | 自动检测方式 |
|---|---|
| GitHub Token | `gh auth token`（GitHub CLI） |
| 仓库 owner/name | `git remote get-url origin` |
| 仓库路径 | 默认当前项目 |
| 发件人 | Laravel 的 `MAIL_FROM_*` 配置 |

### 多仓库配置（可选）

编辑 `config/weekly-report.php`：

```php
'repositories' => [
    ['path' => '/path/to/repo-a'],
    ['path' => '/path/to/repo-b'],
],
```

## 使用方法

```bash
# 生成并发送预览邮件
php artisan report:weekly

# 仅预览，不发送邮件
php artisan report:weekly --dry-run

# 生成上周的周报
php artisan report:weekly --weeks-ago=1
```

### 确认流程

- 在预览邮件中点击**确认发送**，周报将发送给所有收件人
- 点击**取消**，丢弃本次周报
- 链接 24 小时后过期

## 自定义模板

```bash
php artisan vendor:publish --tag=weekly-report-views
```

## 目录结构

```
src/
├── Commands/WeeklyReportCommand.php    # report:weekly 命令
├── Http/Controllers/                   # Signed URL 确认/取消控制器
├── Mail/                               # 预览邮件和正式邮件
├── Services/
│   ├── GitLogParser.php                # Git log 解析 + issue 提取 + 自动识别仓库
│   ├── GitHubClient.php                # GitHub API 客户端（自动获取 token）
│   └── ReportGenerator.php             # 编排器
└── WeeklyReportServiceProvider.php     # 自动注册的 ServiceProvider
```

## License

MIT
