<title id="title">飞书配置 - {$title}</title>
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

<div id="container" class="container p-4">
    <div class="row col-space16">
        <div class="col-xs12 title-large center-vertical mb-4">
            <mdui-icon name="chat" class="mr-2"></mdui-icon>
            <span>飞书配置</span>
        </div>
        <div class="col-xs12">
            <div class="config-section">
                <div class="config-title">飞书自定义机器人</div>
                <form class="row col-space16" id="feishuForm">
                    <div class="col-md12">
                        <mdui-text-field
                                label="Webhook URL"
                                name="url"
                                type="url"
                                variant="outlined"
                                required
                                helper="格式：https://open.feishu.cn/open-apis/bot/v2/hook/xxx"
                        ></mdui-text-field>
                    </div>
                    <div class="col-md12">
                        <mdui-text-field
                                label="签名密钥"
                                name="secret"
                                variant="outlined"
                                helper="可选。机器人开启「签名校验」时填写"
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
                        <mdui-button id="saveFeishu" icon="save" type="submit">
                            保存配置
                        </mdui-button>
                        <mdui-button id="testFeishu" icon="send" class="test-button">
                            测试通知
                        </mdui-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script id="script" src="/notify/static/js/feishu.js?v={$__v}"></script>
