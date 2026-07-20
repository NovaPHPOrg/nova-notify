<title id="title">通知渠道 - {$title}</title>
<style id="style">
    .action-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }
    .config-section {
        margin-bottom: 24px;
    }
    .config-title {
        font-size: 18px;
        font-weight: 500;
        margin-bottom: 16px;
        color: var(--mdui-color-primary);
    }
    .notice-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 24px;
    }
</style>

<div id="container" class="container p-4">
    <div class="row col-space16">
        <div class="col-xs12 title-large center-vertical mb-4">
            <mdui-icon name="notifications" class="mr-2"></mdui-icon>
            <span>通知渠道</span>
        </div>
        <div class="col-xs12">
            <div class="config-section">
                <div class="config-title">渠道配置</div>
                <form class="row col-space16" id="notify-config-form">
                    <div class="col-md12">
                        <mdui-select
                            name="default_channel"
                            label="默认通知渠道"
                            variant="outlined"
                            required
                        >
                            <mdui-menu-item value="email">邮件</mdui-menu-item>
                            <mdui-menu-item value="webhook">Webhook</mdui-menu-item>
                        </mdui-select>
                    </div>

                    {if sizeof($notices) > 0}
                    <div class="col-md12">
                        <div class="config-title" style="margin-top: 8px;">需要发送通知的事件</div>
                        <div class="notice-grid">
                            {foreach $notices as $key => $value}
                                <mdui-checkbox name="{$key}">{$value}</mdui-checkbox>
                            {/foreach}
                        </div>
                    </div>
                    {/if}

                    <div class="col-md12 action-buttons">
                        <mdui-button type="submit" icon="save">保存配置</mdui-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script id="script" src="/notify/static/js/config.js?v={$__v}"></script>
