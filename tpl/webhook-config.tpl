<title id="title">Webhook配置 - {$title}</title>
<style id="style">
    .action-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }
    mdui-card {
        width: 100%;
    }
    .test-button {
        margin-left: 8px;
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
</style>

<div id="container" class="container">
    <div class="row col-space16 p-4">
        <div class="col-xs12 title-large center-vertical mb-4">
            <mdui-icon name="link" class="mr-2"></mdui-icon>
            <span>Webhook配置</span>
        </div>
        <div class="col-xs12">
            <div class="config-section">
                <div class="config-title">Webhook服务器配置</div>
                <form class="row col-space16" id="webhookForm">
                    <div class="col-md12">
                        <mdui-text-field
                            label="Webhook URL"
                            name="url"
                            type="url"
                            variant="outlined"
                            required
                            helper="接收通知的Webhook地址"
                        ></mdui-text-field>
                    </div>
                    <div class="col-md12">
                        <mdui-text-field
                            label="认证头部"
                            name="auth_header"
                            variant="outlined"
                            helper="例如: Authorization: Bearer your-token"
                        ></mdui-text-field>
                    </div>
                    <div class="col-md6">
                        <mdui-text-field
                            label="超时时间(秒)"
                            name="timeout"
                            type="number"
                            variant="outlined"
                            helper="请求超时时间，1-300秒"
                        ></mdui-text-field>
                    </div>
                    <div class="col-md12 action-buttons">
                        <mdui-button id="saveWebhook" icon="save" type="submit">
                            保存配置
                        </mdui-button>
                        <mdui-button id="testWebhook" icon="send" class="test-button">
                            测试通知
                        </mdui-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script id="script">
    window.pageLoadFiles = [
        'Form'
    ];

    window.pageOnLoad = function (loading) {

        $.form.manage("/notify/webhook/config","#webhookForm")
        // 处理渠道状态开关变化
        $("#testWebhook").on("click", function() {
            let loading = new Loading(document.body,"测试中");
            loading.show();
            $.request.postForm("/notify/webhook/test", {

            },function (data) {
                loading.close();
                if (data.code === 200){
                    $.toaster.success("测试成功")
                }else{
                    $.toaster.error(data.msg)
                }

            });
        });
        window.pageOnUnLoad = function () {
            // 页面卸载时的清理工作
        };
    };
</script>