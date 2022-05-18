// For some reason, just importing the original here fixes typing errors in forum/index.ts
import ComposerState from 'flarum/forum/states/ComposerState';

declare module 'flarum/forum/states/ComposerState' {
    export default interface ComposerState {
        fields: {
            content: any
            isAnonymous?: boolean
        } | undefined
    }
}
