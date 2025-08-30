import React from 'react';

const AboutPage: React.FC = () => {
  return (
    <div className="max-w-6xl mx-auto p-8">
      <h1 className="text-3xl font-bold mb-4">About Web Hatchery</h1>

      <p className="mb-4">
        Web Hatchery is a fun AI playground — a space where I share experiments, games, apps, stories, and anything else AI-related that I find interesting. Think of it as a collection of prototypes and ideas, some polished, some rough, but all open to explore.
      </p>

      <p className="mb-4">
        Everything here is free. You're welcome to:
      </p>

      <ul className="list-disc list-inside mb-4">
        <li>Browse and try out projects.</li>
        <li>Take the code and use it in your own work.</li>
        <li>Contribute improvements or build your own version.</li>
      </ul>

      <p className="mb-4">
        The only request is attribution — please credit Web Hatchery if you reuse the work.
      </p>

      <p className="mb-4">
        Donations are welcome via Ko-fi, but they're never required.
      </p>

      <h2 className="text-2xl font-semibold mt-6 mb-3">🥚 About Eggs</h2>

      <p className="mb-4">
        Eggs are Web Hatchery's playful little currency. You'll receive some eggs every day just for visiting. You can spend them to:
      </p>

      <ul className="list-disc list-inside mb-4">
        <li>Request new features</li>
        <li>Boost existing feature requests to raise their priority</li>
      </ul>

      <p>
        The more eggs a feature has, the more likely it is to be built.
      </p>
    </div>
  );
};

export default AboutPage;
