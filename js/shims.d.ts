// For some reason, just importing the original here fixes typing errors in forum/index.ts
import OriginalDiscussionComposer from 'flarum/forum/components/DiscussionComposer';

declare module 'flarum/forum/components/DiscussionComposer' {
    export default interface DiscussionComposer {
        isAnonymous?: boolean
    }
}
declare module 'flarum/forum/components/ReplyComposer' {
    export default interface ReplyComposer {
        isAnonymous?: boolean
    }
}
