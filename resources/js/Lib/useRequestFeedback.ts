import { useState } from 'react';
export function useRequestFeedback() {
  const [failure, setFailure] = useState('');
  const fail = () => {
    setFailure(
      'The request failed. Your changes may not have been saved. Reload to check before trying again.',
    );
    return false;
  };
  return {
    failure,
    options: { onStart: () => setFailure(''), onNetworkError: fail, onHttpException: fail },
  };
}
