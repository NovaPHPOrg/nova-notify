<title id="title">企业微信配置 - {$title}</title>
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
            <mdui-icon name="chat" class="mr-2"></mdui-icon>
            <span>企业微信配置</span>
        </div>
        <div class="col-xs12">
            <div class="config-section">
                <div class="config-title">企业微信应用配置</div>
                <form class="row col-space16" id="wechatForm">
                    <div class="col-md12">
                        <mdui-text-field
                            label="企业ID"
                            name="corp_id"
                            variant="outlined"
                            required
                            helper="企业微信管理后台获取的企业ID"
                        ></mdui-text-field>
                    </div>
                    <div class="col-md12">
                        <mdui-text-field
                            label="应用Secret"
                            name="corp_secret"
                            type="password"
                            variant="outlined"
                            required
                            helper="企业微信应用Secret"
                        ></mdui-text-field>
                    </div>
                    <div class="col-md12">
                        <mdui-text-field
                            label="应用ID"
                            name="agent_id"
                            variant="outlined"
                            required
                            helper="企业微信应用ID"
                        ></mdui-text-field>
                    </div>
                    <div class="col-md12">
                        <mdui-text-field
                            label="默认收件人"
                            name="default_recipient"
                            variant="outlined"
                            helper="默认接收通知的用户ID"
                        ></mdui-text-field>
                    </div>
                    <div class="col-md12 action-buttons">
                        <mdui-button id="saveWechat" icon="save" type="submit">
                            保存配置
                        </mdui-button>
                        <mdui-button id="testWechat" icon="send" class="test-button">
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

        $.form.manage("/notify/wechat/config","#wechatForm");
        $("#testWechat").on("click", function() {
            let loading = new Loading(document.body,"测试中");
            loading.show();
            $.request.postForm("/notify/wechat/test", {

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