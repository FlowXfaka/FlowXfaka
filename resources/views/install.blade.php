@php
    $report = $report ?? ['ready' => false, 'runtime' => [], 'paths' => []];
    $values = $values ?? [];
    $errors = $errors ?? [];
    $result = $result ?? null;
    $checks = array_merge($report['runtime'], $report['paths']);

    $appUrl = trim((string) ($values['app_url'] ?? ''));
    $siteName = trim((string) ($values['site_name'] ?? ''));

    if ($appUrl === '' || str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1')) {
        $appUrl = request()->getSchemeAndHttpHost();
    }

        if ($siteName === '' || in_array(strtolower($siteName), ['flowx', 'flowxfaka'], true)) {
        $siteName = 'FlowXfaka';
    }

    $initialStep = $result ? 4 : ($errors !== [] ? 3 : 1);
@endphp
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>安装向导</title>
    <style>
        :root {
            --bg: #f5f6f8;
            --panel: #ffffff;
            --line: #e5e7eb;
            --text: #111827;
            --muted: #6b7280;
            --brand: #2563eb;
            --brand-soft: #dbeafe;
            --ok: #16a34a;
            --ok-soft: #dcfce7;
            --bad: #dc2626;
            --bad-soft: #fee2e2;
            --shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif;
        }

        .wrap {
            max-width: 920px;
            margin: 0 auto;
            padding: 36px 16px;
        }

        .shell {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .head {
            padding: 24px 24px 16px;
            border-bottom: 1px solid var(--line);
        }

        .head h1 {
            margin: 0;
            font-size: 32px;
            line-height: 1.1;
        }

        .body {
            padding: 24px;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .step {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 48px;
            padding: 0 12px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fff;
            color: var(--muted);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .step[disabled] {
            cursor: default;
            opacity: 0.55;
        }

        .step.is-active {
            color: var(--brand);
            border-color: #bfdbfe;
            background: #eff6ff;
        }

        .step.is-done {
            color: var(--ok);
        }

        .step-index {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 999px;
            background: #f3f4f6;
            font-size: 12px;
            font-weight: 800;
        }

        .panel {
            display: none;
            padding: 20px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: #fff;
        }

        .panel.is-active {
            display: block;
        }

        .panel h2 {
            margin: 0 0 16px;
            font-size: 22px;
            line-height: 1.2;
        }

        .alert {
            margin-bottom: 16px;
            padding: 14px 16px;
            border: 1px solid transparent;
            border-radius: 14px;
            line-height: 1.7;
        }

        .alert strong {
            display: block;
            margin-bottom: 6px;
        }

        .alert ul {
            margin: 8px 0 0;
            padding-left: 18px;
        }

        .alert-error {
            color: #991b1b;
            background: var(--bad-soft);
            border-color: #fecaca;
        }

        .alert-ok {
            color: #166534;
            background: var(--ok-soft);
            border-color: #bbf7d0;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat {
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fff;
        }

        .stat strong {
            display: block;
            margin-top: 8px;
            font-size: 20px;
            line-height: 1.2;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 24px;
            padding: 0 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .badge-ok {
            color: var(--ok);
            background: var(--ok-soft);
        }

        .badge-bad {
            color: var(--bad);
            background: var(--bad-soft);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
        }

        th,
        td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid var(--line);
        }

        th {
            background: #f9fafb;
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .state-ok {
            color: var(--ok);
            font-weight: 800;
        }

        .state-bad {
            color: var(--bad);
            font-weight: 800;
        }

        .desc {
            color: var(--muted);
            font-size: 13px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .field,
        .field-wide {
            display: grid;
            gap: 8px;
        }

        .field-wide {
            grid-column: 1 / -1;
        }

        label {
            font-size: 14px;
            font-weight: 700;
        }

        input {
            width: 100%;
            min-height: 48px;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            background: #fff;
            color: var(--text);
            font: inherit;
        }

        input:focus {
            outline: none;
            border-color: #93c5fd;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .note {
            color: var(--muted);
            font-size: 13px;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 18px;
        }

        .actions .spacer {
            flex: 1;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 124px;
            min-height: 44px;
            padding: 0 16px;
            border: 0;
            border-radius: 12px;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
        }

        .btn:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .btn-main {
            color: #fff;
            background: var(--brand);
        }

        .btn-sub {
            color: var(--text);
            background: #f3f4f6;
            border: 1px solid var(--line);
        }

        @media (max-width: 760px) {
            .steps,
            .stats,
            .grid {
                grid-template-columns: 1fr;
            }

            .body,
            .head {
                padding-left: 16px;
                padding-right: 16px;
            }

            .actions {
                flex-direction: column;
            }

            .actions .spacer {
                display: none;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body data-ready="{{ $report['ready'] ? '1' : '0' }}" data-step="{{ $initialStep > 3 ? 3 : $initialStep }}">
    <div class="wrap">
        <div class="shell">
            <div class="head">
                <h1>{{ $result ? '安装完成' : '安装向导' }}</h1>
            </div>

            <div class="body">
                @if ($result)
                    <div class="panel is-active">
                        <div class="alert alert-ok">
                            <strong>安装成功</strong>
                            管理员账号：{{ $result['admin_name'] }}
                        </div>

                        @if ($result['warnings'] !== [])
                            <div class="alert alert-error">
                                <strong>提示</strong>
                                <ul>
                                    @foreach ($result['warnings'] as $warning)
                                        <li>{{ $warning }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="actions">
                            <div class="spacer"></div>
                            <a class="btn btn-main" href="{{ $result['admin_url'] }}">进入后台</a>
                        </div>
                    </div>
                @else
                    @if ($errors !== [])
                        <div class="alert alert-error">
                            <strong>安装失败</strong>
                            <ul>
                                @foreach ($errors as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="post" action="{{ route('install.store') }}" id="install-form" novalidate>
                        @csrf
                        <input type="hidden" name="app_url" value="{{ $appUrl }}">
                        <input type="hidden" name="site_name" value="{{ $siteName }}">
                        <input type="hidden" name="use_redis" value="0">
                        <input type="hidden" name="db_host" value="127.0.0.1">
                        <input type="hidden" name="db_port" value="3306">

                        <div class="steps">
                            <button type="button" class="step is-active" data-step-target="1">
                                <span class="step-index">1</span>
                                <span>环境检查</span>
                            </button>
                            <button type="button" class="step" data-step-target="2">
                                <span class="step-index">2</span>
                                <span>数据库</span>
                            </button>
                            <button type="button" class="step" data-step-target="3">
                                <span class="step-index">3</span>
                                <span>管理员</span>
                            </button>
                        </div>

                        <section class="panel is-active" data-step-panel="1">
                            <h2>环境检查</h2>

                            <div class="stats">
                                <div class="stat">
                                    <span class="badge {{ $report['ready'] ? 'badge-ok' : 'badge-bad' }}">
                                        {{ $report['ready'] ? 'Ready' : 'Blocked' }}
                                    </span>
                                    <strong>{{ $report['ready'] ? '可以继续' : '先处理失败项' }}</strong>
                                </div>
                                <div class="stat">
                                    <span class="badge badge-ok">PHP</span>
                                    <strong>{{ PHP_VERSION }}</strong>
                                </div>
                            </div>

                            <table>
                                <thead>
                                    <tr>
                                        <th>项目</th>
                                        <th>结果</th>
                                        <th>说明</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($checks as $check)
                                        <tr>
                                            <td>{{ $check['label'] }}</td>
                                            <td>
                                                <span class="{{ $check['status'] ? 'state-ok' : 'state-bad' }}">
                                                    {{ $check['status'] ? '通过' : '失败' }}
                                                </span>
                                            </td>
                                            <td class="desc">{{ $check['detail'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="actions">
                                <div class="spacer"></div>
                                <button class="btn btn-main" type="button" data-next="2" {{ $report['ready'] ? '' : 'disabled' }}>下一步</button>
                            </div>
                        </section>

                        <section class="panel" data-step-panel="2">
                            <h2>数据库</h2>

                            <div class="grid">
                                <div class="field">
                                    <label for="db_database">数据库名</label>
                                    <input id="db_database" name="db_database" type="text" value="{{ $values['db_database'] }}" required>
                                </div>
                                <div class="field">
                                    <label for="db_username">账号</label>
                                    <input id="db_username" name="db_username" type="text" value="{{ $values['db_username'] }}" required>
                                </div>
                                <div class="field-wide">
                                    <label for="db_password">密码</label>
                                    <input id="db_password" name="db_password" type="password" autocomplete="new-password">
                                </div>
                            </div>

                            <div class="actions">
                                <button class="btn btn-sub" type="button" data-prev="1">上一步</button>
                                <div class="spacer"></div>
                                <button class="btn btn-main" type="button" data-next="3">下一步</button>
                            </div>
                        </section>

                        <section class="panel" data-step-panel="3">
                            <h2>管理员</h2>

                            <div class="grid">
                                <div class="field-wide">
                                    <label for="admin_name">账号</label>
                                    <input id="admin_name" name="admin_name" type="text" value="{{ $values['admin_name'] }}" required>
                                </div>
                                <div class="field-wide">
                                    <label for="admin_password">密码</label>
                                    <input id="admin_password" name="admin_password" type="password" autocomplete="new-password" required>
                                </div>
                                <div class="field-wide">
                                    <label for="admin_password_confirmation">确认密码</label>
                                    <input id="admin_password_confirmation" name="admin_password_confirmation" type="password" autocomplete="new-password" required>
                                </div>
                            </div>

                            <div class="actions">
                                <button class="btn btn-sub" type="button" data-prev="2">上一步</button>
                                <div class="spacer"></div>
                                <button class="btn btn-main" type="submit" {{ $report['ready'] ? '' : 'disabled' }}>立即安装</button>
                            </div>
                        </section>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if (! $result)
        <script>
            const ready = document.body.dataset.ready === '1';
            const startStep = Number(document.body.dataset.step || '1');
            const tabs = Array.from(document.querySelectorAll('[data-step-target]'));
            const panels = Array.from(document.querySelectorAll('[data-step-panel]'));
            let currentStep = startStep;
            let maxStep = ready ? Math.max(1, startStep) : 1;

            const showStep = (step) => {
                currentStep = step;

                tabs.forEach((tab) => {
                    const target = Number(tab.dataset.stepTarget);
                    tab.disabled = target > maxStep;
                    tab.classList.toggle('is-active', target === step);
                    tab.classList.toggle('is-done', target < step);
                });

                panels.forEach((panel) => {
                    panel.classList.toggle('is-active', Number(panel.dataset.stepPanel) === step);
                });
            };

            const validCurrentPanel = () => {
                const panel = document.querySelector(`[data-step-panel="${currentStep}"]`);

                if (!panel) {
                    return true;
                }

                const fields = Array.from(panel.querySelectorAll('input'));

                for (const field of fields) {
                    if (!field.checkValidity()) {
                        field.reportValidity();
                        return false;
                    }
                }

                return true;
            };

            document.querySelectorAll('[data-next]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!validCurrentPanel()) {
                        return;
                    }

                    const next = Number(button.dataset.next);
                    maxStep = Math.max(maxStep, next);
                    showStep(next);
                });
            });

            document.querySelectorAll('[data-prev]').forEach((button) => {
                button.addEventListener('click', () => {
                    showStep(Number(button.dataset.prev));
                });
            });

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    const target = Number(tab.dataset.stepTarget);

                    if (target <= maxStep) {
                        showStep(target);
                    }
                });
            });

            showStep(currentStep);
        </script>
    @endif
</body>
</html>
