{{ header }}

<div class="bb-main-content">
    <table class="bb-box" cellpadding="0" cellspacing="0">
        <tbody>
            <tr>
                <td class="bb-content bb-pb-0" align="center">
                    <table class="bb-icon bb-icon-lg bb-bg-blue" cellspacing="0" cellpadding="0">
                        <tbody>
                            <tr>
                                <td valign="middle" align="center">
                                    <img src="{{ 'mail' | icon_url }}" class="bb-va-middle" width="40" height="40" alt="Icon">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <h1 class="bb-text-center bb-m-0 bb-mt-md">Reply to Your Contact Message</h1>
                </td>
            </tr>
            <tr>
                <td>
                    <table cellpadding="0" cellspacing="0">
                        <tbody>
                            <tr>
                                <td class="bb-content">
                                    <p>Dear {{ contact_name }},</p>

                                    <p>Thank you for contacting us. We have reviewed your message and here is our response:</p>

                                    <div class="bb-content-block">
                                        <h4>Your Original Message:</h4>
                                        <table class="bb-table" cellspacing="0" cellpadding="0">
                                            <thead>
                                                <tr>
                                                    <th width="80px"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {% if contact_subject %}
                                                    <tr>
                                                        <td>Subject:</td>
                                                        <td class="bb-font-strong bb-text-left"> {{ contact_subject }} </td>
                                                    </tr>
                                                {% endif %}
                                                {% if contact_content %}
                                                    <tr>
                                                        <td colspan="2">Message:</td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" class="bb-font-strong"><i>{{ contact_content }}</i></td>
                                                    </tr>
                                                {% endif %}
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="bb-content-block bb-mt-lg">
                                        <h4>Our Response:</h4>
                                        <div class="bb-reply-content">
                                            {{ admin_reply_message }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="bb-content bb-text-center bb-pt-0 bb-pb-xl" align="center">
                                    <p>If you have any additional questions, feel free to reply to this email or contact us again.</p>

                                    <p>Best regards,<br>{{ site_title }} Team</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</div>

{{ footer }}
