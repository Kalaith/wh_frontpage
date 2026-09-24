import React from 'react';
import { HatchlingMark } from '../components/HatchlingMark';

const AboutPage: React.FC = () => {
  return (
    <div className="hatchery-about">
      <div className="hatchery-container">
        <section className="hatchery-hero" aria-labelledby="about-title">
          <div className="hatchery-hero-copy">
            <span className="hatchery-eyebrow">
              <span className="hatchery-live-dot" /> AN INDEPENDENT DIGITAL
              PLAYGROUND
            </span>
            <h1 id="about-title">
              Small experiments.
              <br />
              <em>Big what-ifs.</em>
            </h1>
            <p>
              Games, useful little tools, and wonderfully strange ideas.
              <br className="hatchery-desktop-break" /> Built with curiosity.
              Shared while they’re growing.
            </p>
            <div className="hatchery-hero-actions">
              <a href="/#explore" className="hatchery-button">
                Explore the collection <span aria-hidden="true">↗</span>
              </a>
              <a href="#about-webhatchery" className="hatchery-text-link">
                About the hatchery <span aria-hidden="true">→</span>
              </a>
            </div>
          </div>
          <div className="hatchery-hero-art" aria-hidden="true">
            <div className="hatchery-orbit hatchery-orbit-outer" />
            <div className="hatchery-orbit hatchery-orbit-inner" />
            <span className="hatchery-art-star">✳</span>
            <span className="hatchery-art-spark">✧</span>
            <span className="hatchery-art-dot" />
            <div className="hatchery-egg-paper">
              <HatchlingMark />
              <span>
                something good
                <br />
                is hatching.
              </span>
            </div>
            <span className="hatchery-art-note">a work in curiosity</span>
          </div>
        </section>
        <section
          className="hatchery-about-copy"
          id="about-webhatchery"
          aria-labelledby="about-webhatchery-title"
        >
          <h2 id="about-webhatchery-title" className="text-3xl font-bold mb-4">
            About Web Hatchery
          </h2>

          <p className="mb-4">
            Web Hatchery is a fun AI playground — a space where I share
            experiments, games, apps, stories, and anything else AI-related that
            I find interesting. Think of it as a collection of prototypes and
            ideas, some polished, some rough, but all open to explore.
          </p>

          <p className="mb-4">Everything here is free. You're welcome to:</p>

          <ul className="list-disc list-inside mb-4">
            <li>Browse and try out projects.</li>
            <li>Take the code and use it in your own work.</li>
            <li>Contribute improvements or build your own version.</li>
          </ul>

          <p className="mb-4">
            The only request is attribution — please credit Web Hatchery if you
            reuse the work.
          </p>

          <p className="mb-4">
            Donations are welcome via Ko-fi, but they're never required.
          </p>

          <h2 className="text-2xl font-semibold mt-6 mb-3">🥚 About Eggs</h2>

          <p className="mb-4">
            <strong>Earn Daily Eggs:</strong> Our playful currency rewards your
            curiosity. Drop in daily to collect eggs to hatch new ideas or
            influence the Hatchery's direction!
          </p>

          <ul className="list-disc list-inside mb-4">
            <li>Request new experiments</li>
            <li>Boost your favorite ideas to raise their priority</li>
          </ul>

          <p>The more eggs an idea has, the more likely it is to be built.</p>
        </section>
      </div>
    </div>
  );
};

export default AboutPage;
