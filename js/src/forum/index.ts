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
import PostPreview from 'flarum/forum/components/PostPreview';
import PostsUserPage from 'flarum/forum/components/PostsUserPage';

function extendComposerInit(this: DiscussionComposer | ReplyComposer) {
    app.composer.fields!.isAnonymous = !!app.forum.attribute('defaultAnonymousPost');
}

function extendComposerHeaderItems(this: DiscussionComposer | ReplyComposer, items: ItemList<any>) {
    if (!app.forum.attribute('canAnonymousSwitch')) {
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
    data.isAnonymous = !!app.composer.fields!.isAnonymous;
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
            if ("tags" in app.composer.fields) {
                vdom.children[index] = anonymousAvatar(app.forum, '.ComposerBody-avatar', "Discussion", app.composer.fields.tags);
            } else if (this instanceof ReplyComposer && app.composer.body.attrs.discussion.data.relationships && "tags" in app.composer.body.attrs.discussion.data.relationships) {
                vdom.children[index] = anonymousAvatar(app.forum, '.ComposerBody-avatar', "Post", app.composer.body.attrs.discussion.data.relationships.tags.data);
            } else {
                vdom.children[index] = anonymousAvatar(app.forum, '.ComposerBody-avatar');
            }
        });
    });
}

function getAnonymousProfile(profile: { [key: string]: any }) {
    return {
        url: profile.user_avatar_url,
        alt: profile.user_username,
    };
}

function anonymousAvatar(post: Discussion | Post | Forum, className: string = '', composerType?: string, selectedTags?: []) {
    const anonymousAvatarUrl = post.attribute('anonymousAvatarUrl');
    const allTags = post.attribute('anonymousImposters');
    var imageSrc = processDisplayAvatar(anonymousAvatarUrl, composerType, allTags, selectedTags);
    if (imageSrc) {
        if (imageSrc.url) {
            return m('img.Avatar.Avatar--anonymous' + className, {
                src: imageSrc.url,
                alt: imageSrc.alt,
            });
        } else {
            return m('span.Avatar ComposerBody-avatar' + className, {
                alt: imageSrc.alt,
                style: '--avatar-bg: #a0e5b3;',
            }, imageSrc.alt.charAt(0).toUpperCase());
        }
    }

    return m('span.Avatar.Avatar--anonymous' + className, app.translator.trans('clarkwinkelmann-anonymous-posting.lib.userMeta.initials'));
}

function processDisplayAvatar(anonymousAvatarUrl, composerType, allTags, selectedTags) {
    var imageSrc = null;
    if (selectedTags && allTags && composerType in allTags) {
        var composerTypeTags = allTags[composerType];
        for (const tag of selectedTags) {
            /* Different structure for Post and Discussion
             * .type = Post
             * .data.type = Discussion
             */
            var tagId = tag.type == "tags"? tag.id: (tag.data.type == "tags"? Number(tag.data.id): null);
            if (tagId in composerTypeTags) {
                imageSrc = getAnonymousProfile(composerTypeTags[tagId]);
                break;
            }
        }; 
    }
    if (imageSrc == null && anonymousAvatarUrl) {
        imageSrc = {
            url: anonymousAvatarUrl,
            alt: app.translator.trans('clarkwinkelmann-anonymous-posting.lib.userMeta.username'),
        };
    }
    return imageSrc;
}

function processDisplayName(post, selectedTags) {
    const allTags = post.attribute('anonymousImposters');
    if (selectedTags && allTags && 'Post' in allTags) {
        var composerTypeTags = allTags['Post'];
        for (const tag of selectedTags) {
            var tagId = tag.id;
            if (tagId in composerTypeTags) {
                return getAnonymousProfile(composerTypeTags[tagId]).alt;
            }
        }; 
    }
    return app.translator.trans('clarkwinkelmann-anonymous-posting.lib.userMeta.username');
}

app.initializers.add('anonymous-posting', () => {
    extend(CommentPost.prototype, 'headerItems', function (items) {
        const {post} = this.attrs;

        if (!post.attribute('isAnonymous')) {
            return;
        }

        items.setContent('user', m('.PostUser', m('h3', [
            anonymousAvatar(post, '.PostUser-avatar'),
            m('span.username', app.translator.trans('clarkwinkelmann-anonymous-posting.lib.userMeta.username')),
        ])));

        const anonymousUser = Model.hasOne<User>('anonymousUser').call(post);

        if (!anonymousUser) {
            if (!post.attribute('isAnonymousMe')) {
                return;
            }

            let className = '.AnonymousPostPrivacyMine';
            let tooltipText = app.translator.trans('clarkwinkelmann-anonymous-posting.forum.postPrivacy.mineHelp');
            let labelText = app.translator.trans('clarkwinkelmann-anonymous-posting.forum.postPrivacy.mine');

            if (app.current.matches(PostsUserPage)) {
                className = '.AnonymousPostPrivacyProfile';
                tooltipText = app.translator.trans('clarkwinkelmann-anonymous-posting.forum.postPrivacy.profileHelp');
                labelText = app.translator.trans('clarkwinkelmann-anonymous-posting.forum.postPrivacy.profile');
            }

            items.add(
                'anonymousUserPrivacy',
                Tooltip.component({
                    text: tooltipText,
                }, m('span.AnonymousPostPrivacy' + className, labelText)),
                90 // Just after the original user label
            );

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
                            if (app.composer.body.attrs.discussion.data.relationships && "tags" in app.composer.body.attrs.discussion.data.relationships) {
                                // Replace preview avatar with specific tags settings
                                vdom3.children[index] = anonymousAvatar(app.forum, '.PostUser-avatar', "Post", app.composer.body.attrs.discussion.data.relationships.tags.data);
                            } else {
                                vdom3.children[index] = anonymousAvatar(app.forum, '.PostUser-avatar');
                            }
                        }

                        // Replace preview username
                        if (child.attrs.className === 'username') {
                            if (app.composer.body.attrs.discussion.data.relationships && "tags" in app.composer.body.attrs.discussion.data.relationships) {
                                // Replace preview display name with specific tags settings
                                child.text = processDisplayName(app.forum, app.composer.body.attrs.discussion.data.relationships.tags.data);
                            } else {
                                child.text = app.translator.trans('clarkwinkelmann-anonymous-posting.lib.userMeta.username');
                            }
                            
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

    extend(PostPreview.prototype, 'view', function (vdom) {
        if (!this.attrs.post.attribute('isAnonymous')) {
            return;
        }

        vdom.children.forEach(preview => {
            if (!preview || !preview.attrs || !preview.attrs.className || preview.attrs.className.indexOf('PostPreview-content') === -1) {
                return;
            }

            preview.children.forEach((child, index) => {
                if (child && child.attrs && child.attrs.className && child.attrs.className.indexOf('Avatar') === 0) {
                    preview.children.splice(index, 1, anonymousAvatar(this.attrs.post));
                }
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
            return this.attrs.post.attribute('isAnonymous');
        });
    });

    extend(DiscussionComposer.prototype, 'oninit', extendComposerInit);
    extend(DiscussionComposer.prototype, 'headerItems', extendComposerHeaderItems);
    extend(DiscussionComposer.prototype, 'data', extendComposerData);
    extend(DiscussionComposer.prototype, 'view', extendComposerView);
    extend(ReplyComposer.prototype, 'oninit', extendComposerInit);
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
