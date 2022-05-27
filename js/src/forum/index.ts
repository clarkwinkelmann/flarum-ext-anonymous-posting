import app from 'flarum/forum/app';
import {extend, override} from 'flarum/common/extend';
import humanTime from 'flarum/common/utils/humanTime';
import extractText from 'flarum/common/utils/extractText';
import ItemList from 'flarum/common/utils/ItemList';
import icon from 'flarum/common/helpers/icon';
import Button from 'flarum/common/components/Button';
import Link from 'flarum/common/components/Link';
import Switch from 'flarum/common/components/Switch';
import Tooltip from 'flarum/common/components/Tooltip';
import Model from 'flarum/common/Model';
import Discussion from 'flarum/common/models/Discussion';
import Post from 'flarum/common/models/Post';
import Forum from 'flarum/common/models/Forum';
import User from 'flarum/common/models/User';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import PostControls from 'flarum/forum/utils/PostControls';
import CommentPost from 'flarum/forum/components/CommentPost';
import Composer from 'flarum/forum/components/Composer';
import DiscussionComposer from 'flarum/forum/components/DiscussionComposer';
import ReplyComposer from 'flarum/forum/components/ReplyComposer';
import PostUser from 'flarum/forum/components/PostUser';
import DiscussionListItem from 'flarum/forum/components/DiscussionListItem';
import ReplyPlaceholder from 'flarum/forum/components/ReplyPlaceholder';
import TerminalPost from 'flarum/forum/components/TerminalPost';

function extendComposerHeaderItems(this: DiscussionComposer | ReplyComposer, items: ItemList<any>) {
    if (!app.forum.attribute('canAnonymousPost')) {
        return;
    }

    const helpText = app.translator.trans('clarkwinkelmann-anonymous-posting.forum.composerControls.anonymizeHelp');
    const helpTextPosition = app.forum.attribute('anonymousHelpTextPosition');

    items.add('anonymous-posting', Switch.component({
        className: helpTextPosition === 'visible' ? 'AnonymousCheckbox--multiline' : '',
        state: !!app.composer.fields!.isAnonymous,
        onchange: (value: boolean) => {
            app.composer.fields!.isAnonymous = value;
        },
    }, m('span.AnonymousCheckboxLabel', [
        app.translator.trans('clarkwinkelmann-anonymous-posting.forum.composerControls.anonymize'),
        helpTextPosition === 'tooltip' ? [' ', Tooltip.component({
            text: helpText,
        }, icon('fas fa-info-circle', {
            className: 'AnonymousCheckboxInfo',
        }))] : null,
        helpTextPosition === 'visible' ? m('.helpText', helpText) : null,
    ])), -10);
}

function extendComposerData(this: DiscussionComposer | ReplyComposer, data: any) {
    if (app.composer.fields!.isAnonymous) {
        data.isAnonymous = true;
    }
}

function extendComposerView(this: DiscussionComposer | ReplyComposer, vdom: any) {
    if (!app.composer.fields!.isAnonymous) {
        return;
    }

    if (!vdom || !Array.isArray(vdom.children)) {
        return;
    }

    // Loop through <ConfirmDocumentUnload> children
    vdom.children.forEach(vdom => {
        if (!vdom || !Array.isArray(vdom.children)) {
            return;
        }

        // Loop through .ComposerBody children
        vdom.children.forEach((child, index) => {
            if (!child || !child.attrs || !child.attrs.className || child.attrs.className.indexOf('ComposerBody-avatar') === -1) {
                return;
            }

            vdom.children[index] = anonymousAvatar(app.forum, '.ComposerBody-avatar');
        });
    });
}

function anonymousAvatar(post: Discussion | Post | Forum, className: string = '') {
    const src = post.attribute('anonymousAvatarUrl');

    if (src) {
        return m('img.Avatar.Avatar--anonymous' + className, {
            src,
            alt: app.translator.trans('clarkwinkelmann-anonymous-posting.lib.userMeta.username'),
        });
    }

    return m('span.Avatar.Avatar--anonymous' + className, app.translator.trans('clarkwinkelmann-anonymous-posting.lib.userMeta.initials'));
}

app.initializers.add('anonymous-posting', () => {
    extend(CommentPost.prototype, 'headerItems', function (items) {
        // @ts-ignore
        const post = this.attrs.post as Post;

        if (!post.attribute('isAnonymous')) {
            return;
        }

        items.setContent('user', m('.PostUser', m('h3', [
            anonymousAvatar(post, '.PostUser-avatar'),
            m('span.username', app.translator.trans('clarkwinkelmann-anonymous-posting.lib.userMeta.username')),
        ])));

        const anonymousUser = Model.hasOne<User>('anonymousUser').call(post);

        if (!anonymousUser) {
            return;
        }

        // Provide an altered post object that the PostUser component can read the user from
        const alteredPost = new Post({
            ...post.data,
            relationships: {
                ...post.data.relationships,
                user: post.data.relationships!.anonymousUser,
            },
        });

        items.add(
            'anonymousUser',
            PostUser.component({
                post: alteredPost,
                cardVisible: this.cardVisible,
                oncardshow: () => {
                    this.cardVisible = true;
                    m.redraw();
                },
                oncardhide: () => {
                    this.cardVisible = false;
                    m.redraw();
                },
            }),
            90 // Just after the original user label
        );
    });

    extend(ReplyPlaceholder.prototype, 'view', function (vdom0) {
        if (!app.composer.fields!.isAnonymous) {
            return;
        }

        if (!vdom0 || !Array.isArray(vdom0.children)) {
            return;
        }

        // Loop through .Post children
        vdom0.children.forEach(vdom1 => {
            if (!vdom1 || !Array.isArray(vdom1.children)) {
                return;
            }

            // Loop through .Post-header children
            vdom1.children.forEach((vdom2) => {
                if (!vdom2 || !Array.isArray(vdom2.children)) {
                    return;
                }

                // Loop through .PostUser children
                vdom2.children.forEach((vdom3, index3) => {
                    if (!vdom3 || !Array.isArray(vdom3.children)) {
                        return;
                    }

                    // Loop through <h3> children
                    vdom3.children.forEach((child, index) => {
                        if (!child || !child.attrs || !child.attrs.className) {
                            return;
                        }

                        // Replace preview avatar
                        if (child.attrs.className.indexOf('PostUser-avatar') !== -1) {
                            vdom3.children[index] = anonymousAvatar(app.forum, '.PostUser-avatar');
                        }

                        // Replace preview username
                        if (child.attrs.className === 'username') {
                            child.text = app.translator.trans('clarkwinkelmann-anonymous-posting.lib.userMeta.username');
                        }
                    });

                    // Remove ul.PostUser-badges which would show the actor's badges
                    if (vdom3.attrs && vdom3.attrs.className && vdom3.attrs.className.indexOf('PostUser-badges') !== -1) {
                        vdom2.children.splice(index3, 1);
                    }
                });
            });
        });
    });

    extend(DiscussionListItem.prototype, 'view', function (vdom) {
        // @ts-ignore
        const discussion = this.attrs.discussion as Discussion;

        if (!discussion.attribute('isAnonymous')) {
            return;
        }

        vdom.children.forEach(vdom => {
            if (!vdom || !vdom.attrs || !vdom.attrs.className || vdom.attrs.className.indexOf('DiscussionListItem-content') === -1) {
                return;
            }

            vdom.children.forEach(vdom => {
                if (!vdom || vdom.tag !== Tooltip) {
                    return;
                }

                vdom.attrs.text = app.translator.trans('core.forum.discussion_list.started_text', {
                    username: app.translator.trans('clarkwinkelmann-anonymous-posting.lib.userMeta.username'),
                    ago: humanTime(discussion.createdAt()),
                });

                vdom.children.forEach(vdom => {
                    if (!vdom || vdom.tag !== Link) {
                        return;
                    }

                    vdom.children = [
                        anonymousAvatar(discussion),
                    ];
                });
            });
        });
    });

    override(TerminalPost.prototype, 'view', function (original, ...args) {
        const discussion = this.attrs.discussion;

        if (this.attrs.lastPost && discussion.replyCount()) {
            if (discussion.lastPostedUser()) {
                return original(...args);
            }

            // Loading discussion.lastPost for the sole purpose of getting post.isAnonymous would be detrimental to performance
            // Instead we'll replace all "[deleted] replied" texts with a generic message that doesn't include a username

            return m('span', [
                icon('fas fa-reply'),
                ' ',
                app.translator.trans('clarkwinkelmann-anonymous-posting.forum.discussionList.genericReplyText', {
                    ago: humanTime(discussion.lastPostedAt()),
                }),
            ]);
        }

        if (!discussion.attribute('isAnonymous')) {
            return original(...args);
        }

        return m('span', [
            '', // Keep output the same as original method to maximise compatibility with other extensions
            ' ',
            app.translator.trans('core.forum.discussion_list.started_text', {
                username: m('span.username', app.translator.trans('clarkwinkelmann-anonymous-posting.lib.userMeta.username')),
                ago: humanTime(discussion.createdAt()),
            }),
        ]);
    });

    extend(CommentPost.prototype, 'oninit', function () {
        this.subtree!.check(() => {
            // @ts-ignore
            const post = this.attrs.post as Post;
            return post.attribute('isAnonymous');
        });
    });

    extend(DiscussionComposer.prototype, 'headerItems', extendComposerHeaderItems);
    extend(DiscussionComposer.prototype, 'data', extendComposerData);
    extend(DiscussionComposer.prototype, 'view', extendComposerView);
    extend(ReplyComposer.prototype, 'headerItems', extendComposerHeaderItems);
    extend(ReplyComposer.prototype, 'data', extendComposerData);
    extend(ReplyComposer.prototype, 'view', extendComposerView);

    extend(DiscussionControls, 'moderationControls', function (items, discussion) {
        if (discussion.attribute('canDeAnonymize')) {
            items.add('deanonymize', Button.component({
                icon: 'fas fa-user-secret',
                onclick: () => {
                    if (!confirm(extractText(app.translator.trans('clarkwinkelmann-anonymous-posting.forum.discussionControls.deanonymizeConfirmation')))) {
                        return;
                    }

                    discussion.save({
                        isAnonymous: false,
                    }).then(() => {
                        m.redraw();
                    });
                },
            }, app.translator.trans('clarkwinkelmann-anonymous-posting.forum.discussionControls.deanonymize')));
        }

        if (discussion.attribute('canAnonymize')) {
            items.add('anonymize', Button.component({
                icon: 'fas fa-user-secret',
                onclick: () => {
                    if (!confirm(extractText(app.translator.trans('clarkwinkelmann-anonymous-posting.forum.discussionControls.anonymizeConfirmation')))) {
                        return;
                    }

                    discussion.save({
                        isAnonymous: true,
                    }).then(() => {
                        m.redraw();
                    });
                },
            }, app.translator.trans('clarkwinkelmann-anonymous-posting.forum.discussionControls.anonymize')));
        }
    });

    extend(PostControls, 'moderationControls', function (items, post) {
        if (post.attribute('canDeAnonymize')) {
            items.add('deanonymize', Button.component({
                icon: 'fas fa-user-secret',
                onclick: () => {
                    if (!confirm(extractText(app.translator.trans('clarkwinkelmann-anonymous-posting.forum.postControls.deanonymizeConfirmation')))) {
                        return;
                    }

                    post.save({
                        isAnonymous: false,
                    }).then(() => {
                        m.redraw();
                    });
                },
            }, app.translator.trans('clarkwinkelmann-anonymous-posting.forum.postControls.deanonymize')));
        }

        if (post.attribute('canAnonymize')) {
            items.add('anonymize', Button.component({
                icon: 'fas fa-user-secret',
                onclick: () => {
                    if (post.number() === 1) {
                        if (!confirm(extractText(app.translator.trans('clarkwinkelmann-anonymous-posting.forum.postControls.firstPostConfirmation')))) {
                            return;
                        }
                    }

                    post.save({
                        isAnonymous: true,
                    }).then(() => {
                        m.redraw();
                    });
                },
            }, app.translator.trans('clarkwinkelmann-anonymous-posting.forum.postControls.anonymize')));
        }
    });

    // Flarum will try to focus the checkbox in ReplyComposer instead of the body. To work around this, we'll temporarily disable the field while this method runs
    override(Composer.prototype, 'focus', function (original) {
        const $anonymousCheckbox = this.$('.item-anonymous-posting input');

        $anonymousCheckbox.prop('disabled', true);

        const returnValue = original();

        $anonymousCheckbox.prop('disabled', false);

        return returnValue;
    });
});
