import React, { useState, useEffect } from 'react';
import { ProjectSuggestion, trackerApi, ProjectSuggestionComment } from '../../api/trackerApi';
import { useAuth } from '../../stores/authStore';
import { useToastStore } from '../../stores/toastStore';
import { ConfirmModal } from '../ui/ConfirmModal';
import { useQueryClient } from '@tanstack/react-query';

interface IdeaDetailModalProps {
    idea: ProjectSuggestion;
    onClose: () => void;
}

export const IdeaDetailModal: React.FC<IdeaDetailModalProps> = ({ idea, onClose }) => {
    const { user } = useAuth();
    const toast = useToastStore();
    const isAdmin = user?.role === 'admin';
    const [comments, setComments] = useState<ProjectSuggestionComment[]>([]);
    const [newComment, setNewComment] = useState('');
    const [loading, setLoading] = useState(false);
    const [publishing, setPublishing] = useState(false);

    // Confirmation modal state
    const [confirmState, setConfirmState] = useState<{
        isOpen: boolean;
        type: 'publish' | 'delete' | null;
    }>({ isOpen: false, type: null });

    // For invalidation
    const queryClient = useQueryClient();

    useEffect(() => {
        loadComments();
    }, [idea.id]);

    const loadComments = async () => {
        try {
            if (!idea.id) return;
            const data = await trackerApi.getSuggestionComments(idea.id);
            setComments(data);
        } catch (err) {
            console.error("Failed to load comments", err);
        }
    };

    const handleAddComment = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newComment.trim() || !idea.id) return;

        setLoading(true);
        try {
            await trackerApi.addSuggestionComment(idea.id, newComment, user ? { id: user.id as number, name: user.name || 'User' } : undefined);
            setNewComment('');
            loadComments();
        } catch (err) {
            console.error("Failed to add comment", err);
            toast.error("Failed to add comment");
        } finally {
            setLoading(false);
        }
    };

    const handlePublish = async () => {
        if (!idea.id) return;
        setConfirmState({ isOpen: false, type: null });
        setPublishing(true);
        try {
            await trackerApi.publishSuggestion(idea.id);
            toast.success("Idea published successfully!");
            queryClient.invalidateQueries({ queryKey: ['tracker'] });
            onClose();
        } catch (err) {
            console.error("Failed to publish", err);
            toast.error("Failed to publish idea");
        } finally {
            setPublishing(false);
        }
    };

    const handleDelete = async () => {
        if (!idea.id) return;
        setConfirmState({ isOpen: false, type: null });
        setPublishing(true);
        try {
            await trackerApi.deleteProjectSuggestion(idea.id);
            queryClient.invalidateQueries({ queryKey: ['tracker'] });
            onClose();
        } catch (err) {
            console.error("Failed to delete", err);
            toast.error("Failed to delete idea");
            setPublishing(false);
        }
    };

    return (
        <>
            {/* Confirmation Modals */}
            <ConfirmModal
                isOpen={confirmState.isOpen && confirmState.type === 'publish'}
                title="Publish as Project"
                message="Are you sure you want to publish this idea? It will be moved to the Projects list."
                confirmText="Publish"
                variant="info"
                onConfirm={handlePublish}
                onCancel={() => setConfirmState({ isOpen: false, type: null })}
            />
            <ConfirmModal
                isOpen={confirmState.isOpen && confirmState.type === 'delete'}
                title="Delete Suggestion"
                message="Are you sure you want to DELETE this suggestion? This cannot be undone."
                confirmText="Delete"
                variant="danger"
                onConfirm={handleDelete}
                onCancel={() => setConfirmState({ isOpen: false, type: null })}
            />

            <div className="fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4">
                <div className="bg-white rounded-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl flex flex-col">

                    {/* Header */}
                    <div className="p-6 border-b border-gray-200 flex justify-between items-start bg-gray-50 rounded-t-xl sticky top-0 z-10">
                        <div>
                            <h2 className="text-2xl font-bold text-gray-900">{idea.name}</h2>
                            <div className="flex items-center gap-2 mt-2">
                                <span className="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-medium">{idea.suggested_group}</span>
                                <span className="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">{idea.status}</span>
                                <span className="text-sm text-gray-500 ml-2">Suggested by {idea.submitted_by || 'Anonymous'} on {new Date(idea.created_at).toLocaleDateString()}</span>
                            </div>
                        </div>
                        <button onClick={onClose} className="text-gray-400 hover:text-gray-600 p-2 rounded-full hover:bg-gray-200 transition-colors">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {/* Content */}
                    <div className="p-8 space-y-8 flex-grow">
                        {/* Description */}
                        <div className="prose max-w-none">
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">Description</h3>
                            <p className="text-gray-700 leading-relaxed whitespace-pre-wrap">{idea.description}</p>

                            {idea.rationale && (
                                <div className="mt-6 bg-blue-50 p-4 rounded-lg border border-blue-100">
                                    <h4 className="text-md font-semibold text-blue-900 mb-1">Rationale & Goals</h4>
                                    <p className="text-blue-800 text-sm">{idea.rationale}</p>
                                </div>
                            )}
                        </div>

                        {/* Admin Actions */}
                        {isAdmin && (
                            <div className="border-t border-gray-200 pt-6">
                                <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Admin Actions</h3>
                                <div className="flex gap-3">
                                    <button
                                        onClick={() => setConfirmState({ isOpen: true, type: 'publish' })}
                                        disabled={publishing}
                                        className="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 flex items-center gap-2"
                                    >
                                        {publishing ? 'Processing...' : '🚀 Publish as Project'}
                                    </button>

                                    <button
                                        onClick={() => setConfirmState({ isOpen: true, type: 'delete' })}
                                        disabled={publishing}
                                        className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 flex items-center gap-2"
                                    >
                                        🗑️ Delete Suggestion
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* Comments Section */}
                        <div className="border-t border-gray-200 pt-8">
                            <h3 className="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                                Discussion
                                <span className="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-sm font-normal">{comments.length}</span>
                            </h3>

                            <div className="space-y-6 mb-8">
                                {comments.length === 0 ? (
                                    <p className="text-gray-500 italic">No comments yet. Be the first to start the discussion!</p>
                                ) : (
                                    comments.map((comment) => (
                                        <div key={comment.id} className="flex gap-4">
                                            <div className="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-white flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0">
                                                {(comment.user_name || 'A')[0].toUpperCase()}
                                            </div>
                                            <div className="flex-grow">
                                                <div className="bg-gray-50 p-4 rounded-2xl rounded-tl-none">
                                                    <div className="flex justify-between items-baseline mb-1">
                                                        <span className="font-semibold text-gray-900">{comment.user_name}</span>
                                                        <span className="text-xs text-gray-400">{new Date(comment.created_at).toLocaleString()}</span>
                                                    </div>
                                                    <p className="text-gray-700 whitespace-pre-wrap">{comment.content}</p>
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>

                            {/* Add Comment */}
                            <form onSubmit={handleAddComment} className="flex gap-4 items-start">
                                <div className="flex-grow">
                                    <textarea
                                        value={newComment}
                                        onChange={(e) => setNewComment(e.target.value)}
                                        placeholder="Share your thoughts on this idea..."
                                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent min-h-[100px] resize-y"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    disabled={loading || !newComment.trim()}
                                    className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium whitespace-nowrap"
                                >
                                    {loading ? 'Posting...' : 'Post Comment'}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

