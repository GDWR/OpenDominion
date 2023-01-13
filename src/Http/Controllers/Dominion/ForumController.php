<?php

namespace OpenDominion\Http\Controllers\Dominion;

use DB;
use Illuminate\Http\Request;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\RankingsHelper;
use OpenDominion\Http\Requests\Dominion\Forum\CreatePostRequest;
use OpenDominion\Http\Requests\Dominion\Forum\CreateThreadRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Forum;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\RankingsService;
use OpenDominion\Services\ForumService;

class ForumController extends AbstractDominionController
{
    public const RESULTS_PER_PAGE = 50;

    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();
        $lastRead = $dominion->forum_last_read;
        $this->updateDominionForumLastRead($dominion);

        $protectionService = app(ProtectionService::class);
        $forumService = app(ForumService::class);
        $threads = $forumService->getThreads($dominion->round);

        return view('pages.dominion.forum.index', [
            'forumThreads' => $threads,
            'lastRead' => $lastRead,
            'round' => $dominion->round,
            'protectionService' => $protectionService,
            'resultsPerPage' => static::RESULTS_PER_PAGE,
        ]);
    }

    public function getCreate() // getCreateThread?
    {
        $dominion = $this->getSelectedDominion();
        $round = $dominion->round;

        try {
            $this->guardAgainstProtection();
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        return view('pages.dominion.forum.create', compact(
            'round'
        ));
    }

    public function postCreate(CreateThreadRequest $request) // postCreateThread
    {
        $dominion = $this->getSelectedDominion();
        $forumService = app(ForumService::class);

        try {
            $this->guardAgainstRepeatOffenders();
            $this->guardAgainstProtection();
            $thread = $forumService->createThread(
                $dominion,
                $request->get('title'),
                $request->get('body')
            );
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your thread has been created');
        return redirect()->route('dominion.forum.thread', $thread);
    }

    public function getThread(Forum\Thread $thread)
    {
        try {
            $this->guardAgainstCrossRound($thread);
            $this->guardAgainstProtection();
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        $dominion = $this->getSelectedDominion();
        $this->updateDominionForumLastRead($dominion);

        $posts = $thread->posts()->paginate(static::RESULTS_PER_PAGE);

        $rankingsHelper = app(RankingsHelper::class);
        $rankingsService = app(RankingsService::class);

        return view('pages.dominion.forum.thread', compact(
            'thread',
            'posts',
            'rankingsHelper',
            'rankingsService'
        ));
    }

    public function postReply(CreatePostRequest $request, Forum\Thread $thread)
    {
        try {
            $this->guardAgainstCrossRound($thread);
            $this->guardAgainstRepeatOffenders();
            $this->guardAgainstProtection();
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        $dominion = $this->getSelectedDominion();
        $forumService = app(ForumService::class);

        try {
            $forumService->postReply($dominion, $thread, $request->get('body'));
            $this->updateDominionForumLastRead($dominion);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $posts = $thread->posts()->paginate(static::RESULTS_PER_PAGE);
        $request->session()->flash('alert-success', 'Your message has been posted');
        return redirect()->route('dominion.forum.thread', [$thread, 'page' => $posts->lastPage()]);
    }

    public function getDeletePost(Forum\Post $post)
    {
        try {
            $this->guardForPost($post);
            $this->guardAgainstProtection();
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        return view('pages.dominion.forum.delete-post', compact(
            'post'
        ));
    }

    public function postDeletePost(Request $request, Forum\Post $post)
    {
        $dominion = $this->getSelectedDominion();
        $forumService = app(ForumService::class);

        try {
            $this->guardForPost($post);
            $this->guardAgainstProtection();
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        $forumService->deletePost($dominion, $post);

        $request->session()->flash('alert-success', 'Post successfully deleted.');
        return redirect()->route('dominion.forum.thread', $post->thread);
    }

    public function getDeleteThread(Forum\Thread $thread)
    {
        try {
            $this->guardForThread($thread);
            $this->guardAgainstProtection();
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        $thread->load('dominion.realm', 'posts.dominion.realm');

        return view('pages.dominion.forum.delete-thread', compact(
            'thread'
        ));
    }

    public function postDeleteThread(Request $request, Forum\Thread $thread)
    {
        $dominion = $this->getSelectedDominion();
        $forumService = app(ForumService::class);

        try {
            $this->guardForThread($thread);
            $this->guardAgainstProtection();
        } catch (GameException $e) {
            return redirect()
                ->route('dominion.forum')
                ->withErrors([$e->getMessage()]);
        }

        $forumService->deleteThread($dominion, $thread);

        $request->session()->flash('alert-success', 'Thread successfully deleted.');
        return redirect()->route('dominion.forum');
    }

    public function getFlagPost(Request $request, Forum\Post $post)
    {
        $dominion = $this->getSelectedDominion();
        $forumService = app(ForumService::class);

        $forumService->flagPost($dominion, $post);

        $request->session()->flash('alert-success', 'Post successfully flagged for removal.');
        return redirect()->route('dominion.forum.thread', $post->thread);
    }

    public function getFlagThread(Request $request, Forum\Thread $thread)
    {
        $dominion = $this->getSelectedDominion();
        $forumService = app(ForumService::class);

        $forumService->flagThread($dominion, $thread);

        $request->session()->flash('alert-success', 'Thread successfully flagged for removal.');
        return redirect()->route('dominion.forum.thread', $thread);
    }

    /**
     * Throws exception if trying to view a thread outside of the round.
     *
     * @param Forum\Thread $thread
     * @throws GameException
     */
    protected function guardAgainstCrossRound(Forum\Thread $thread): void
    {
        if ($this->getSelectedDominion()->round_id !== (int)$thread->round_id) {
            throw new GameException('No permission to view thread.');
        }
    }

    /**
     * Throws exception if the selected dominion is not the thread's creator.
     *
     * @param Thread $thread
     * @throws GameException
     */
    protected function guardForThread(Forum\Thread $thread): void
    {
        if ($this->getSelectedDominion()->id !== (int)$thread->dominion_id) {
            throw new GameException('No permission to moderate thread.');
        }
    }

    /**
     * Throws exception if the selected dominion is not the post's creator.
     *
     * @param Post $post
     * @throws GameException
     */
    protected function guardForPost(Forum\Post $post): void
    {
        if ($this->getSelectedDominion()->id !== (int)$post->dominion_id) {
            throw new GameException('No permission to moderate post.');
        }
    }

    /**
     * Throws exception if the selected dominion has abused posting privileges
     *
     * @throws GameException
     */
    protected function guardAgainstRepeatOffenders(): void
    {
        $flaggedThreadCount = Forum\Post::where('flagged_for_removal', true)->where('dominion_id', $this->getSelectedDominion()->id)->count();
        $flaggedPostCount = Forum\Post::where('flagged_for_removal', true)->where('dominion_id', $this->getSelectedDominion()->id)->count();
        if (($flaggedThreadCount + $flaggedPostCount) >= 5) {
            throw new GameException('You have been banned from posting for the remainder of the round.');
        }
    }

    /**
     * Throws exception if the selected dominion is still under protection
     *
     * @throws GameException
     */
    protected function guardAgainstProtection(): void
    {
        $protectionService = app(ProtectionService::class);
        if ($protectionService->isUnderProtection($this->getSelectedDominion())) {
            throw new GameException('You cannot access the forum while under protection.');
        }
    }

    protected function updateDominionForumLastRead(Dominion $dominion): void
    {
        // Avoid using save method, which recalculates tick
        DB::table('dominions')
            ->where('id', $dominion->id)
            ->update([
                'forum_last_read' => now()
            ]);
    }
}
