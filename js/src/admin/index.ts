import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';

const avatarsSettingKey = 'anonymous-posting.formulaireAvatars';
const translationPrefix = 'clarkwinkelmann-anonymous-posting.admin.settings.';

interface Avatar {
    formId: string
    fieldKey: string
    fieldValue: string
    avatarUrl: string
}

app.initializers.add('anonymous-posting', () => {
    app.extensionData
        .for('clarkwinkelmann-anonymous-posting')
        .registerSetting({
            setting: 'anonymous-posting.defaultAnonymity',
            label: app.translator.trans(translationPrefix + 'defaultAnonymity'),
            type: 'switch',
        })
        .registerSetting({
            setting: 'anonymous-posting.defaultAnonymityWhenAbleToSwitch',
            type: 'switch',
            label: app.translator.trans(translationPrefix + 'defaultAnonymityWhenAbleToSwitch'),
        })
        .registerSetting({
            setting: 'anonymous-posting.alwaysAnonymiseEdits',
            type: 'switch',
            label: app.translator.trans(translationPrefix + 'alwaysAnonymiseEdits'),
            help: app.translator.trans(translationPrefix + 'alwaysAnonymiseEditsHelp'),
        })
        .registerSetting({
            setting: 'anonymous-posting.composerHelpTextPosition',
            type: 'select',
            options: {
                visible: app.translator.trans(translationPrefix + 'composerHelpTextPositionVisible'),
                tooltip: app.translator.trans(translationPrefix + 'composerHelpTextPositionTooltip'),
                hidden: app.translator.trans(translationPrefix + 'composerHelpTextPositionHidden'),
            },
            default: 'visible',
            label: app.translator.trans(translationPrefix + 'composerHelpTextPosition'),
            help: app.translator.trans(translationPrefix + 'composerHelpTextPositionHelp'),
        })
        .registerSetting(function (this: ExtensionPage) {
            let avatars: Avatar[];

            try {
                avatars = JSON.parse(this.setting(avatarsSettingKey)());
            } catch (e) {
                // do nothing, we'll reset to something usable
            }

            // @ts-ignore variable used before assignment, it's fine
            if (!Array.isArray(avatars)) {
                avatars = [];
            }

            return m('.Form-group', [
                m('label', app.translator.trans(translationPrefix + 'avatars')),
                m('.helpText', app.translator.trans(translationPrefix + 'avatarsHelp', {
                    a: m('a', {
                        href: 'https://kilowhat.net/flarum/extensions/formulaire',
                        target: '_blank',
                        rel: 'noopener',
                    }),
                })),
                m('table', [
                    m('thead', m('tr', [
                        m('th', app.translator.trans(translationPrefix + 'avatarFormId')),
                        m('th', app.translator.trans(translationPrefix + 'avatarFieldKey')),
                        m('th', app.translator.trans(translationPrefix + 'avatarFieldValue')),
                        m('th', app.translator.trans(translationPrefix + 'avatarAvatarUrl')),
                        m('th'),
                    ])),
                    m('tbody', [
                        avatars.map((sound, index) => m('tr', [
                            m('td', m('input.FormControl', {
                                type: 'text',
                                value: sound.formId || '',
                                onchange: (event: InputEvent) => {
                                    sound.formId = (event.target as HTMLInputElement).value;
                                    this.setting(avatarsSettingKey)(JSON.stringify(avatars));
                                },
                            })),
                            m('td', m('input.FormControl', {
                                type: 'text',
                                value: sound.fieldKey || '',
                                onchange: (event: InputEvent) => {
                                    sound.fieldKey = (event.target as HTMLInputElement).value;
                                    this.setting(avatarsSettingKey)(JSON.stringify(avatars));
                                },
                            })),
                            m('td', m('input.FormControl', {
                                type: 'text',
                                value: sound.fieldValue || '',
                                onchange: (event: InputEvent) => {
                                    sound.fieldValue = (event.target as HTMLInputElement).value;
                                    this.setting(avatarsSettingKey)(JSON.stringify(avatars));
                                },
                            })),
                            m('td', m('input.FormControl', {
                                type: 'text',
                                value: sound.avatarUrl || '',
                                onchange: (event: InputEvent) => {
                                    sound.avatarUrl = (event.target as HTMLInputElement).value;
                                    this.setting(avatarsSettingKey)(JSON.stringify(avatars));
                                },
                            })),
                            m('td', Button.component({
                                className: 'Button Button--icon',
                                icon: 'fas fa-times',
                                onclick: () => {
                                    avatars.splice(index, 1);

                                    this.setting(avatarsSettingKey)(avatars.length > 0 ? JSON.stringify(avatars) : null);
                                },
                            })),
                        ])),
                        m('tr', m('td', {
                            colspan: 5,
                        }, Button.component({
                            className: 'Button Button--block',
                            onclick: () => {
                                avatars.push({
                                    formId: '',
                                    fieldKey: '',
                                    fieldValue: '',
                                    avatarUrl: '',
                                });

                                this.setting(avatarsSettingKey)(JSON.stringify(avatars));
                            },
                        }, app.translator.trans(translationPrefix + 'avatarAdd'))))
                    ]),
                ]),
            ]);
        })
        .registerPermission({
            permission: 'anonymous-posting.use',
            icon: 'fas fa-user-secret',
            label: app.translator.trans('clarkwinkelmann-anonymous-posting.admin.permissions.use'),
        }, 'start')
        .registerPermission({
            permission: 'anonymous-posting.reveal',
            icon: 'fas fa-user-secret',
            label: app.translator.trans('clarkwinkelmann-anonymous-posting.admin.permissions.reveal'),
        }, 'moderate')
        .registerPermission({
            permission: 'anonymous-posting.moderate',
            icon: 'fas fa-user-secret',
            label: app.translator.trans('clarkwinkelmann-anonymous-posting.admin.permissions.moderate'),
        }, 'moderate');
});
