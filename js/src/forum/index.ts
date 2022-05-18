import app from 'flarum/forum/app';
import {extend, override} from 'flarum/common/extend';
import humanTime from 'flarum/common/utils/humanTime';
import extractText from 'flarum/common/utils/extractText';
import ItemList from 'flarum/common/utils/ItemList';
import Button from 'flarum/common/components/Button';
import Link from 'flarum/common/components/Link';
import Switch from 'flarum/common/components/Switch';
import Tooltip from 'flarum/common/components/Tooltip';
import Model from 'flarum/common/Model';
import Discussion from 'flarum/common/models/Discussion';
import Post from 'flarum/common/models/Post';
import User from 'flarum/common/models/User';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import PostControls from 'flarum/forum/utils/PostControls';
import CommentPost from 'flarum/forum/components/CommentPost';
import Composer from 'flarum/forum/components/Composer';
import DiscussionComposer from 'flarum/forum/components/DiscussionComposer';
import ReplyComposer from 'flarum/forum/components/ReplyComposer';
import PostUser from 'flarum/forum/components/PostUser';
import DiscussionListItem from 'flarum/forum/components/DiscussionListItem';

function extendComposerHeaderItems(this: DiscussionComposer | ReplyComposer, items: ItemList<any>) {
    if (!app.forum.attribute('canAnonymousPost')) {
        return;
    }

    items.add('anonymous-posting', Switch.component({
        state: !!this.isAnonymous,
        onchange: (value: boolean) => {
            this.isAnonymous = value;
        },
    }, m('span.AnonymousCheckboxLabel', [
        app.translator.trans('clarkwinkelmann-anonymous-posting.forum.composerControls.anonymize'),
        m('.helpText', app.translator.trans('clarkwinkelmann-anonymous-posting.forum.composerControls.anonymizeHelp')),
    ])), -10);
}

function extendComposerData(this: DiscussionComposer | ReplyComposer, data: any) {
    if (this.isAnonymous) {
        data.isAnonymous = true;
    }
}

function anonymousAvatar(post: Discussion | Post, className: string = '') {
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

    extend(CommentPost.prototype, 'oninit', function () {
        this.subtree!.check(() => {
            // @ts-ignore
            const post = this.attrs.post as Post;
            return post.attribute('isAnonymous');
        });
    });

    extend(DiscussionComposer.prototype, 'headerItems', extendComposerHeaderItems);
    extend(DiscussionComposer.prototype, 'data', extendComposerData);
    extend(ReplyComposer.prototype, 'headerItems', extendComposerHeaderItems);
    extend(ReplyComposer.prototype, 'data', extendComposerData);

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
