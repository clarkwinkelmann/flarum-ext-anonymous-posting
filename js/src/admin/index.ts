import app from 'flarum/admin/app';

app.initializers.add('anonymous-posting', () => {
    app.extensionData
        .for('clarkwinkelmann-anonymous-posting')
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
