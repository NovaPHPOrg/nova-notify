<title id="title">通知渠道 - {$title}</title>
<style id="style">

    mdui-card{
        width: 100%;
    }
    
    .channel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }
    
    .channel-title {
        font-size: 18px;
        font-weight: 500;
        display: flex;
        align-items: center;
    }


    .action-buttons {
        display: flex;
        justify-content: flex-end;
        margin-top: 16px;
    }
    
    .default-channel-card {
        margin-bottom: 16px;
    }
    
    .default-channel-form {
        display: flex;
        align-items: center;
    }

    .default-channel-options mdui-radio {
        margin-right: 24px;
    }
</style>

<div id="container" class="container">
    <div class="col-xs12 title-large center-vertical mb-4">
        <mdui-icon name="notifications" class="refresh mr-2"></mdui-icon>
        <span >通知渠道</span>
    </div>
    <form id="notify-config-form">
        <!-- 默认通知渠道设置 -->
        <div class="row">
            <div class="col-xs12">
                <mdui-card class="p-4 default-channel-card">
                    <div class="channel-header">
                        <div class="channel-title">
                            <mdui-icon name="notifications" size="small" class="mr-1"></mdui-icon>
                            默认通知渠道
                        </div>
                    </div>
                    <div class="default-channel-form">
                        <mdui-radio-group name="default_channel">
                            <mdui-radio value="email">邮件</mdui-radio>
                            <mdui-radio value="wechat_work">企业微信</mdui-radio>
                            <mdui-radio value="webhook">WebHook</mdui-radio>
                        </mdui-radio-group>
                    </div>
                </mdui-card>
            </div>
        </div>
        
        <!-- 需要发送通知的事件类型 -->
        {if sizeof($notices)> 0}
        <div class="row">
            <div class="col-xs12">
                <mdui-card class="p-4 mb-3">
                    <div class="channel-header">
                        <div class="channel-title">
                            <mdui-icon name="event" size="small" class="mr-1"></mdui-icon>
                            需要发送通知的事件类型
                        </div>
                    </div>
                    <div class="row">

                        {foreach $notices as $key => $value}
                            <div class="col-xs12 col-md3">
                                <mdui-checkbox name="{$key}">{$value}</mdui-checkbox>
                            </div>
                        {/foreach}


                    </div>
                </mdui-card>
            </div>
        </div>
        {/if}
        <!-- 保存按钮 -->
        <div class="row">
            <div class="col-xs12">
                <div class="action-buttons">
                    <mdui-button type="submit" icon="save">保存配置</mdui-button>
                </div>
            </div>
        </div>
    </form>
</div>

<script id="script">
    window.pageLoadFiles = [
        'Form'
    ];

    window.pageOnLoad = function (loading) {
        // 使用 $.form.manage 管理通知配置表单
        $.form.manage("/notify/config", "#notify-config-form");

        window.pageOnUnLoad = function () {
            // 页面卸载时的清理工作
        };

    };
</script>